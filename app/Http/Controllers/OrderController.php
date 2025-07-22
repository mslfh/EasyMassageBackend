<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\AppointmentService;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    protected $orderService;
    protected $appointmentService;
    protected $voucherService;

    public function __construct(
        OrderService $orderService,
        AppointmentService $appointmentService,
        VoucherService $voucherService
    ) {
        $this->appointmentService = $appointmentService;
        $this->orderService = $orderService;
        $this->voucherService = $voucherService;
    }


    public function index(Request $request)
    {
        $start = $request->query('start', 0);
        $count = $request->query('count', 10);
        $filter = $request->query('filter', null);
        $sortBy = $request->query('sortBy', 'id');
        $descending = $request->query('descending', false);

        $orders = $this->orderService->getPaginatedOrders($start, $count, $filter, $sortBy, $descending);

        return response()->json([
            'rows' => $orders['data'],
            'total' => $orders['total'],
        ]);
    }

    public function getOrderByAppointment($appointmentId)
    {
        return response()->json($this->orderService->getOrderByAppointment($appointmentId));
    }

    public function show($id)
    {
        return response()->json($this->orderService->getOrderById($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'order_status' => 'required|string',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'operator_id' => 'nullable|exists:staff,id',
            'operator_name' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'actual_end_time' => 'nullable',
            'actual_start_time' => 'nullable',
            'split_payment' => 'nullable',
        ]);
        // get the appointment
        $appointment = $this->appointmentService->getAppointmentById($data['appointment_id']);

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        // check if the appointment is already paid
        if ($appointment->status == 'finished') {
            return response()->json(['message' => 'Appointment already done'], 400);
        }

        if ($data['payment_method'] == 'unpaid') {
            $data['payment_status'] = 'pending';
        } else if ($data['payment_method'] == 'split_payment' && $data['order_status'] == 'pending') {
            $data['payment_status'] = 'partially_paid';
        } else {
            $data['payment_status'] = 'paid';
        }
        DB::beginTransaction();
        $appointment->status = 'finished';
        $appointment->actual_start_time = $data['actual_start_time'];
        $appointment->actual_end_time = $data['actual_end_time'];
        $appointment->save();

        if ($data['split_payment']) {
            $payment = [];
            foreach ($data['split_payment'] as $index => $split_payment) {
                $payment[$index]['paid_by'] = $split_payment['method']['label'];
                if ($split_payment['method']['label'] != 'Unpaid') {
                    $payment[$index]['payment_status'] = 'Paid';
                    $payment[$index]['paid_amount'] = $split_payment['amount'];
                } else {
                    $payment[$index]['payment_status'] = 'Unpaid';
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

        $order = $this->orderService->updateOrder($appointment->order->id, $data);
        DB::commit();
        return response()->json($order, 201);
    }

    public function finishOrder(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'order_status' => 'required|string',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'operator_id' => 'nullable|exists:staff,id',
            'operator_name' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'actual_end_time' => 'nullable',
            'actual_start_time' => 'nullable',
            'split_payment' => 'nullable',
            'voucher_code' => 'nullable|string',
        ]);

        try {
            $order = $this->orderService->finishOrder($data);
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'order_status' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'payment_method' => 'sometimes|string',
            'total_amount' => 'sometimes|numeric',
            'paid_amount' => 'sometimes|numeric',
            'operator_id' => 'nullable|exists:staff,id',
            'operator_name' => 'nullable|string',
        ]);

        return response()->json($this->orderService->updateOrder($id, $data));
    }

    public function destroy($id)
    {
        return response()->json($this->orderService->deleteOrder($id));
    }
}
