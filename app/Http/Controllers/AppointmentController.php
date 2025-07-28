<?php

namespace App\Http\Controllers;

use App\Services\AppointmentService;
use Illuminate\Http\Request;


class AppointmentController extends BaseController
{
    protected $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(Request $request)
    {
        $start = $request->query('start', 0);
        $count = $request->query('count', 10);
        $filter = $request->query('filter', null);
        $selected = $request->query('selected', null);
        $sortBy = $request->query('sortBy', 'id');
        $descending = $request->query('descending', false);

        $filter = $filter ? json_decode($filter, true) : null;
        $selected = $selected ? json_decode($selected, true) : null;

        $appointments = $this->appointmentService->getPaginatedAppointments($start, $count, $filter, $sortBy, $descending, $selected);

        return response()->json([
            'rows' => $appointments['data'],
            'total' => $appointments['total'],
        ]);
    }

    public function getUserBookingHistory(Request $request)
    {
        $userId = $request->query('id');
        $phone = $request->query('phone');

        return response()->json($this->appointmentService->getUserBookingHistory(
            $userId,
            $phone
        ));
    }

    public function show($id)
    {
        return response()->json($this->appointmentService->getAppointmentById($id));
    }

    public function getServiceAppointments($id)
    {
        return response()->json($this->appointmentService->getServiceAppointments($id));
    }

    public function cancelAppointments($id)
    {
        try {
            $appointment = $this->appointmentService->cancelAppointments($id);
            return response()->json($appointment);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function getBookedServiceByDate(Request $request)
    {
        $date = $request->input('date');
        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }
        $result = $this->appointmentService->getBookedServiceByDate($date);
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $appointment = $this->appointmentService->storeAppointment($data);
        return response()->json($appointment, 201);
    }

    public function makeAppointment(Request $request)
    {
        $data = $request->all();
        try {
            $appointment = $this->appointmentService->makeAppointment(
                $data
            );
            return response()->json($appointment, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create appointment', 'msg' => $e->getMessage()], 500);
        }
    }

    public function takeBreakAppointment(Request $request)
    {
        $data = $request->all();
        try {
            $appointment = $this->appointmentService->takeBreakAppointment($data);
            return response()->json($appointment, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create appointment', 'msg' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $appointmentData = $request->validate([
            'booking_time' => 'nullable|string',
            'booking_date' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_first_name' => 'nullable|string',
            'customer_last_name' => 'nullable|string',
            'is_first' => 'nullable|boolean',
            'customer_phone' => 'nullable|string',
            'customer_email' => 'nullable|string',
            'customer_comments' => 'nullable|string',
            'tag' => 'nullable|string',
            'comments' => 'nullable|string',
            'actual_start_time' => 'nullable|string',
            'actual_end_time' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
        $inputService = $request->input('service') ?? [];
        $staff = $request->input('staff') ?? [];

        $result = $this->appointmentService->updateAppointmentWithService(
            $id,
            $appointmentData,
            $inputService,
            $staff,
        );
        return response()->json($result);
    }

    public function sendSms(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string',
            'appointment_id' => 'nullable|integer',
            'phone_number' => 'required|string',
            'message' => 'required|string',
            'is_schedule_time' => 'nullable|boolean',
            'schedule_time' => 'nullable|date_format:Y-m-d H:i:s',
        ]);
        try {
            $msg = $this->appointmentService->sendSms($data);
            return response()->json([
                'status' => 'success',
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel($id)
    {
        return response()->json($this->appointmentService->cancelAppointments($id));
    }

    public function destroy($id)
    {
        return response()->json($this->appointmentService->deleteAppointment($id));
    }

    public function makeNoShow(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:appointments,id',
        ]);
        return response()->json($this->appointmentService->markAppointmentAsNoShow($data['id']));
    }

    public function getTodayStatistics()
    {
        return response()->json($this->appointmentService->getTodayStatistics());
    }

    public function getTotalStatistics()
    {
        $beginDate = request()->query('beginDate', null);
        $endDate = request()->query('endDate', null);
        return response()->json($this->appointmentService->getTotalStatistics($beginDate, $endDate));
    }

}
