<?php

namespace App\Services;
use App\Contracts\OrderContract;
use App\Models\Order;
use App\Services\AppointmentService;
use App\Services\VoucherService;
use Carbon\Carbon;
use DB;

class OrderService
{
    protected $orderRepository;
    protected $appointmentService;
    protected $voucherService;
    protected $appointmentLogService;

    public function __construct(OrderContract $orderRepository, AppointmentService $appointmentService, VoucherService $voucherService, AppointmentLogService $appointmentLogService)
    {
        $this->orderRepository = $orderRepository;
        $this->appointmentService = $appointmentService;
        $this->voucherService = $voucherService;
        $this->appointmentLogService = $appointmentLogService;
    }

    public function getAllOrders()
    {
        return $this->orderRepository->getAllOrders();
    }

    public function getOrderById($id)
    {
        return $this->orderRepository->getOrderById($id);
    }

    public function getOrderByAppointment($appointmentId)
    {
        return $this->orderRepository->getOrderByAppointment($appointmentId);
    }

    public function createOrder(array $data)
    {
        return $this->orderRepository->createOrder($data);
    }

    public function updateOrder($id, array $data)
    {
        return $this->orderRepository->updateOrder($id, $data);
    }

    public function deleteOrder($id)
    {
        return $this->orderRepository->deleteOrder($id);
    }

    public function getPaginatedOrders($start, $count, $filter, $sortBy, $descending)
    {
        $query = Order::query();

        if ($filter) {
            $query->whereHas('appointment', function ($q) use ($filter) {
                $q
                    ->where('customer_first_name', 'like', "%$filter%")
                    ->orWhere('customer_phone', 'like', "%$filter%")
                    ->orWhereHas('services', function ($q) use ($filter) {
                        $q->where('staff_name', 'like', "%$filter%");
                    });
            });
        }
        $sortDirection = $descending ? 'desc' : 'asc';
        $query->whereHas('appointment', function ($q) {
            $q->where('status', '!=', 'cancelled');
        })->with('appointment.services')
            ->orderBy($sortBy, $sortDirection);

        $total = $query->count();
        $data = $query->skip($start)->take($count)->with('payment')->get();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public function initAppointmentOrder($appointmentId, $total_amount)
    {
        $data = [
            'status' => 'pending',
            'appointment_id' => $appointmentId,
            'payment_method' => 'unpaid',
            'total_amount' => $total_amount,
        ];
        $this->createOrder($data);
    }

    public function finishOrder(array $data)
    {
        // get the appointment
        $appointment = $this->appointmentService->getAppointmentById($data['appointment_id']);

        if (!$appointment) {
            throw new \Exception('Appointment not found', 404);
        }

        if ($data['payment_method'] == 'unpaid') {
            $data['payment_status'] = 'pending';
        } else if ($data['payment_method'] == 'split_payment' && $data['order_status'] == 'pending') {
            $data['payment_status'] = 'partially_paid';
        } else {
            $data['payment_status'] = 'paid';
        }

        DB::beginTransaction();

        try {
            $appointment->status = 'finished';
            $appointment->actual_start_time = $data['actual_start_time'];
            $appointment->actual_end_time = $data['actual_end_time'];
            $appointment->save();

            //voucher full pay
            if (isset($data['voucher_code']) && !$data['split_payment']) {
                $voucherData = $this->voucherService->consumeVoucher(
                    $data['voucher_code'],
                    $data['total_amount'],
                    $appointment->customer_id ?? null,
                    $appointment->id,
                    $appointment->customer_phone,
                    $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
                    $appointment->services->first()->service_title . '( $' . $appointment->services->first()->service_price . ' )'
                );

                if ($voucherData['status'] == 'error') {
                    throw new \Exception($voucherData['message'], 400);
                }
                $voucher = $voucherData['data'];
                $data['payment_note'] = $data['payment_note'] . ' (Voucher Code: ' . $voucher->code . ')';
            }

            if ($data['split_payment']) {
                $payment = [];
                foreach ($data['split_payment'] as $index => $split_payment) {
                    $payment[$index]['paid_by'] = $split_payment['method']['value'];
                    $payment[$index]['remark'] = $data['payment_note'];

                    if ($split_payment['method']['label'] != 'Unpaid') {
                        $payment[$index]['status'] = 'Paid';
                        $payment[$index]['paid_amount'] = $split_payment['amount'];
                        if ($split_payment['method']['label'] == 'Voucher') {
                            $voucherData = $this->voucherService->consumeVoucher(
                                $data['voucher_code'],
                                $split_payment['amount'],
                                $appointment->customer_id ?? null,
                                $appointment->id,
                                $appointment->customer_phone,
                                $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
                                $appointment->services->first()->service_title . '( $' . $appointment->services->first()->service_price . ' )'

                            );
                            if ($voucherData['status'] == 'error') {
                                throw new \Exception($voucherData['message'], 400);
                            }
                            $voucher = $voucherData['data'];
                            $data['payment_note'] = $data['payment_note'] . ' ( Voucher Code: ' . $voucher->code . ' )';
                        }
                    } else {
                        $payment[$index]['status'] = 'Unpaid';
                        $payment[$index]['paid_amount'] = 0;
                    }
                    $payment[$index]['total_amount'] = $split_payment['amount'];
                }
                $data['payment'] = $payment;
                unset($data['split_payment']);
            }

            unset($data['actual_start_time']);
            unset($data['actual_end_time']);

            if ($appointment->order) {
                $order = $this->updateOrder($appointment->order->id, $data);
            } else {
                $data['appointment_id'] = $appointment->id;
                $order = $this->createOrder($data);
            }

            // Log the appointment status change
            $this->appointmentLogService->logCheckedOut(
                $appointment->id,
                $data['paid_amount'] ?? 0,
                $data['payment_method'] ?? 'unpaid',
                $data['payment_note'] ?? '',
                isset($data['voucher_code']) ? $data['voucher_code'] : null,
                $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
                $appointment->services->first()->service_title ?? null
            );

            DB::commit();
            return $order;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getSalesStatistics($startDate = null, $endDate = null)
    {
        if (!$startDate) {
            $startDate = Carbon::today();
        }
        $orders = $this->orderRepository->getOrdersByDateRange($startDate, $endDate);
        $sales['service'] = [
            'sales_qty' => 0,
            'pricing_fees' => 0, // Total fees for all services
            'receivable_fees' => 0, // Total amount expected to be received
            'payments_collected' => 0, // Total amount actually received
        ];
        $sales['no_show'] = [
            'sales_qty' => 0,
            'pricing_fees' => 0,
            'receivable_fees' => 0,
            'payments_collected' => 0,
        ];
        $sales['cancelled'] = [
            'sales_qty' => 0,
            'pricing_fees' => 0,
            'receivable_fees' => 0,
            'payments_collected' => 0,
        ];
        $sales['voucher'] = [
            'sales_qty' => 0,
            'pricing_fees' => 0,
            'receivable_fees' => 0,
            'payments_collected' => 0,
        ];
        $servicesMismatch = [];
        $amountGroupedByPaymentMethod = [];


        foreach ($orders as $order) {
            $service = $order->appointment->services->first();
            if (!$service) {
                continue;
            }
            if ($order->appointment->type == 'no_show') {
                $sales['no_show']['sales_qty'] += 1;
                $sales['no_show']['pricing_fees'] += $service->service_price;
                $sales['no_show']['receivable_fees'] += $order->total_amount;
                $sales['no_show']['payments_collected'] += $order->paid_amount;
            } else if ($order->appointment->status == 'cancelled') {
                $sales['cancelled']['sales_qty'] += 1;
                $sales['cancelled']['pricing_fees'] += $service->service_price;
                $sales['cancelled']['receivable_fees'] += $order->total_amount;
                $sales['cancelled']['payments_collected'] += $order->paid_amount;
            } else {
                $sales['service']['sales_qty'] += 1;
                $sales['service']['pricing_fees'] += $service->service_price;
                $sales['service']['receivable_fees'] += $order->total_amount;
                $sales['service']['payments_collected'] += $order->paid_amount;
            }
            if ($service->service_price != $order->total_amount) {
                $servicesMismatch[] = [
                    'appointment_id' => $order->appointment_id,
                    'customer_name' => $service->customer_name,
                    'pricing_fees' => $service->service_price,
                    'total_amount' => $order->total_amount,
                    'booking_time' => $order->appointment->booking_time,
                    'service_title' => $order->appointment->services->first()->service_title ?? 'N/A',
                    'staff_name' => $order->appointment->services->first()->staff_name ?? 'N/A',
                    'duration' => $order->appointment->services->first()->service_duration ?? 'N/A',
                ];
            }

            if (!isset($order->payment_method)) {
                continue;
            }
            $paymentMethod = $order->payment_method;
            if ($paymentMethod == 'split_payment') {
                $splitPayments = $order->payment()->get();
                foreach ($splitPayments as $splitPayment) {
                    $paymentMethod = $splitPayment->paid_by;
                    if (!isset($amountGroupedByPaymentMethod[$paymentMethod])) {
                        if ($splitPayment->paid_by === 'unpaid') {
                            $amountGroupedByPaymentMethod[$paymentMethod] = (float) number_format($splitPayment->total_amount, 2, '.', '');
                        } else {
                            $amountGroupedByPaymentMethod[$paymentMethod] = (float) number_format($splitPayment->paid_amount, 2, '.', '');
                        }
                    } else {
                        if ($splitPayment->paid_by === 'unpaid') {
                            $amountGroupedByPaymentMethod[$paymentMethod] += (float) number_format($splitPayment->total_amount, 2, '.', '');
                        } else {
                            $amountGroupedByPaymentMethod[$paymentMethod] += (float) number_format($splitPayment->paid_amount, 2, '.', '');
                        }
                        $amountGroupedByPaymentMethod[$paymentMethod] = (float) number_format($amountGroupedByPaymentMethod[$paymentMethod], 2, '.', '');
                    }
                }
            } else {
                if (!isset($amountGroupedByPaymentMethod[$paymentMethod])) {
                    if ($order->payment_method === 'unpaid') {
                        $amountGroupedByPaymentMethod[$paymentMethod] = (float) number_format($order->total_amount, 2, '.', '');
                    } else {
                        $amountGroupedByPaymentMethod[$paymentMethod] = (float) number_format($order->paid_amount, 2, '.', '');
                    }
                } else {
                    if ($order->payment_method === 'unpaid') {
                        $amountGroupedByPaymentMethod[$paymentMethod] += (float) number_format($order->total_amount, 2, '.', '');
                    } else {
                        $amountGroupedByPaymentMethod[$paymentMethod] += (float) number_format($order->paid_amount, 2, '.', '');
                    }
                    $amountGroupedByPaymentMethod[$paymentMethod] = (float) number_format($amountGroupedByPaymentMethod[$paymentMethod], 2, '.', '');
                }
            }
        }
        return [
            'sales' => $sales,
            'servicesMismatch' => $servicesMismatch,
            'amountGroupedByPaymentMethod' => $amountGroupedByPaymentMethod
        ];
    }
}
