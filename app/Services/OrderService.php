<?php

namespace App\Services;
use App\Contracts\OrderContract;
use App\Models\Order;
use App\Services\AppointmentService;
use App\Services\VoucherService;
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
                    ->orWhere('customer_email', 'like', "%$filter%")
                    ->orWhereHas('services', function ($q) use ($filter) {
                        $q->where('staff_name', 'like', "%$filter%");
                    });
            });
        }

        $sortDirection = $descending ? 'desc' : 'asc';
        $query->whereHas('appointment', function ($q) {
            $q->where('status', '!=', 'cancelled');
        })->with('appointment.services')->orderBy($sortBy, $sortDirection);

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

            if (isset($data['voucher_code'])) {
                $voucherData = $this->voucherService->verifyVoucher($data['voucher_code']);
                if ($voucherData['status'] == 'error') {
                    throw new \Exception($voucherData['message'], 400);
                }
                $voucher = $voucherData['data'];
                if ($voucher->remaining_amount < $data['total_amount']) {
                    $voucher->remaining_amount = 0;
                } else {
                    $voucher->remaining_amount -= $data['total_amount'];
                }
                $voucher->save();
                $data['payment_note'] = $data['payment_note'] . '  Voucher Code: ' . $voucher->code;
            }

            if ($data['split_payment']) {
                $payment = [];
                foreach ($data['split_payment'] as $index => $split_payment) {
                    $payment[$index]['paid_by'] = $split_payment['method']['value'];
                    if ($split_payment['method']['label'] != 'Unpaid') {
                        $payment[$index]['status'] = 'Paid';
                        $payment[$index]['paid_amount'] = $split_payment['amount'];
                    } else {
                        $payment[$index]['status'] = 'Unpaid';
                        $payment[$index]['paid_amount'] = 0;
                    }
                    $payment[$index]['total_amount'] = $split_payment['amount'];
                    $payment[$index]['remark'] = $data['payment_note'];
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
}
