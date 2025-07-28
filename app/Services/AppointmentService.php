<?php

namespace App\Services;

use App\Contracts\AppointmentContract;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\ServiceAppointmentService;
use App\Services\UserService;
use App\Services\StaffService;
use App\Services\SmsService;
use App\Services\SystemSettingService;
use App\Services\NotificationService;
use App\Services\AppointmentLogService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;


class AppointmentService
{
    protected $appointmentRepository;
    protected $serviceAppointmentService;
    protected $userService;
    protected $staffService;
    protected $systemSettingService;
    protected $smsService;
    protected $notificationService;
    protected $appointmentLogService;

    public function __construct(
        AppointmentContract $appointmentRepository,
        ServiceAppointmentService $serviceAppointmentService,
        UserService $userService,
        StaffService $staffService,
        SmsService $smsService,
        SystemSettingService $systemSettingService,
        NotificationService $notificationService,
        AppointmentLogService $appointmentLogService
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->serviceAppointmentService = $serviceAppointmentService;
        $this->userService = $userService;
        $this->staffService = $staffService;
        $this->smsService = $smsService;
        $this->systemSettingService = $systemSettingService;
        $this->notificationService = $notificationService;
        $this->appointmentLogService = $appointmentLogService;

    }

    public function getAllAppointments()
    {
        return $this->appointmentRepository->getAll();
    }
    public function getPaginatedAppointments($start, $count, $filter, $sortBy, $descending, $selected)
    {

        $query = Appointment::query();

        if ($filter) {
            if ($filter['field'] == "customer_name") {
                $query->where('customer_first_name', 'like', "%{$filter['value']}%");
            } else if ($filter['field'] == "customer_phone") {
                $query->where('customer_phone', 'like', "%{$filter['value']}%");
            } else if ($filter['field'] == "booking_time") {
                $query->where('booking_time', 'like', "%{$filter['value']}%");
            } else if ($filter['field'] == "therapist") {
                $query->whereHas('services', function ($q) use ($filter) {
                    $q->where('staff_name', $filter['value']);
                });
            }
        }
        if ($selected) {
            if ( $selected['field'] == "deleted" ) {
                $query->where('deleted_at', '!=', null);
            }
            else if ( $selected['field'] == "cancelled" ) {
                $query->where('status', 'cancelled');
            }
            else if ( $selected['field'] == "no_show" ) {
                $query->where('type', 'no_show');
            }
        }

        $sortDirection = $descending ? 'desc' : 'asc';
        $query->with([
            'services' => function ($query) {
                $query->withTrashed();
            }
        ])->withTrashed()->orderBy($sortBy, $sortDirection);

        $total = $query->count();
        $data = $query->skip($start)->take($count)->get();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public function getAppointmentsFromDate($date)
    {
        return $this->appointmentRepository->getByDate($date);
    }

    public function getUserBookingHistory($id, $phone)
    {
        return $this->appointmentRepository->getUserBookingHistory($id, $phone);
    }

    public function getAppointmentById($id)
    {
        return $this->appointmentRepository->getById($id);
    }

    public function getServiceAppointments($id)
    {
        return $this->appointmentRepository->getServiceAppointments($id);
    }

    public function createAppointment(array $data)
    {
        return $this->appointmentRepository->create($data);
    }

    public function updateAppointment($id, array $data)
    {
        return $this->appointmentRepository->update($id, $data);
    }

    public function deleteAppointment($id)
    {
        return $this->appointmentRepository->delete($id);
    }

    public function createServiceAppointment(array $data)
    {
        return $this->serviceAppointmentService->createServiceAppointment($data);
    }

    public function getAppointmentByDate($date)
    {
        return $this->appointmentRepository->getByDate($date);
    }

    public function storeAppointment($data)
    {
        $user = $this->userService->findByField(
            [
                'search' => $data['customer_phone'] ?? '',
                'field' => 'phone',
                'fuzzy' => false
            ]
        );
        if ($user->isEmpty()) {
            if ($data['is_first']) {
                $user = $this->userService->createUser(
                    [
                        'name' => $data['customer_first_name'] . ' ' . $data['customer_last_name'],
                        'first_name' => $data['customer_first_name'] ?? '',
                        'last_name' => $data['customer_last_name'] ?? '',
                        'phone' => $data['customer_phone'] ?? '',
                        'email' => $data['customer_email'] ?? '',
                    ]
                );
                $data['customer_id'] = $user->id;
            }
        } else {
            $user = $user->first();
            $data['customer_id'] = $user->id;
        }

        $data['booking_time'] = $data['booking_date'] . ' ' . $data['booking_time'];
        $appointmentData = $data;
        $appointmentData['tag'] = implode(',', $data['tag']);
        unset($appointmentData['customer_service']);
        unset($appointmentData['booking_date']);
        \DB::beginTransaction();
        try {
            $appointment = $this->createAppointment($appointmentData);
            foreach ($data['customer_service'] as $serviceData) {
                $service = Service::with('package')->findOrFail($serviceData['service']['id']);
                $serviceData['service_id'] = $service->id ?? null;
                $serviceData['staff_id'] = $service->staff_id ?? null;
                $serviceData['package_id'] = $service->package_id ?? null;
                $serviceData['package_title'] = $service->package->title ?? null;
                $serviceData['package_hint'] = $service->package->hint ?? null;
                $serviceData['service_id'] = $service->id ?? null;
                $serviceData['service_title'] = $service->title ?? null;
                $serviceData['service_description'] = $service->description ?? null;
                $serviceData['service_duration'] = $service->duration ?? null;
                $serviceData['service_price'] = $service->price ?? null;
                $serviceData['appointment_id'] = $appointment->id;
                $serviceData['booking_time'] = $appointment->booking_time;
                if ($serviceData["staff"]['id'] == 0) {
                    $recommendedstaff = $this->staffService->getAvailableStaffFromScheduletime(
                        $serviceData['booking_time'],
                        $serviceData['service_duration'],
                    )->first();
                    $serviceData['any_therapist'] = true;
                    if (!$recommendedstaff) {
                        throw new \Exception('No available staff found for the selected time and duration.', 500);
                    }
                    $serviceData['staff_id'] = $recommendedstaff->id ?? '0';
                    $serviceData['staff_name'] = $recommendedstaff->name ?? '';
                } else {
                    $serviceData['any_therapist'] = false;
                    $serviceData['staff_id'] = $serviceData["staff"]['id'];
                    $serviceData['staff_name'] = $serviceData["staff"]['name'];
                }
                $this->createServiceAppointment($serviceData);
            }
            app(OrderService::class)->initAppointmentOrder($appointment->id, $appointment->amount);
            // send notification sms
            $this->sendAppointmentSms($appointment->customer_phone, $appointment->booking_time, $serviceData);

            // Log the appointment creation by staff
            $this->appointmentLogService->logAppointmentBooked(
                $appointment->id,
                true,
                $appointment->booking_time,
                $serviceData['service_title'],
                $serviceData['customer_name'],
                $serviceData['staff_name'],
                $appointment->comments ?? 'Appointment booked by staff',
            );

            \DB::commit();
            return $appointment->load('services');
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function makeAppointment($data)
    {
        $user = $this->userService->findByField(
            [
                'search' => $data['customer_phone'],
                'field' => 'phone',
                'fuzzy' => false
            ]
        );
        if ($user->isEmpty()) {
            $user = $this->userService->createUser(
                [
                    'name' => $data['customer_first_name'] . ' ' . $data['customer_last_name'],
                    'first_name' => $data['customer_first_name'],
                    'last_name' => $data['customer_last_name'],
                    'phone' => $data['customer_phone'],
                    'email' => $data['customer_email'] ?? '',
                ]
            );
        } else {
            $user = $user->first();
        }
        $data['customer_id'] = $user->id;
        $data['booking_time'] = $data['booking_date'] . ' ' . $data['booking_time'];

        $appointmentData = $data;
        unset($appointmentData['customer_service']);
        unset($appointmentData['booking_date']);
        $appointmentData['tag'] = implode(',', $data['tag']);
        \DB::beginTransaction();
        try {
            if ($data['customer_service'][0]['staff_id'] == 0) {
                $appointmentData['type'] = 'unassigned';
            } else {
                $appointmentData['type'] = 'assigned';
            }
            $appointment = $this->createAppointment($appointmentData);
            foreach ($data['customer_service'] as $serviceData) {
                $service = Service::with('package')->findOrFail($serviceData['service_id']);
                $serviceData['package_id'] = $service->package_id ?? null;
                $serviceData['package_title'] = $service->package->title ?? null;
                $serviceData['package_hint'] = $service->package->hint ?? null;
                $serviceData['service_id'] = $service->id ?? null;
                $serviceData['service_title'] = $service->title;
                $serviceData['service_description'] = $service->description;
                $serviceData['service_duration'] = $service->duration;
                $serviceData['service_price'] = $service->price;
                $serviceData['appointment_id'] = $appointment->id;
                $serviceData['booking_time'] = $appointment->booking_time;

                if ($serviceData["staff_id"] == 0) {
                    $recommendedstaff = $this->staffService->getAvailableStaffFromScheduletime(
                        $serviceData['booking_time'],
                        $serviceData['service_duration'],
                    )->first();
                    if (!$recommendedstaff) {
                        throw new \Exception('No available staff found for the selected time and duration.', 500);
                    }
                    $serviceData['any_therapist'] = true;
                    $serviceData['staff_id'] = $recommendedstaff->id ?? '0';
                    $serviceData['staff_name'] = $recommendedstaff->name ?? '';
                } else {
                    $serviceData['any_therapist'] = false;
                }
                $this->createServiceAppointment($serviceData);
            }
            // create following order
            app(OrderService::class)->initAppointmentOrder($appointment->id, $appointment->amount);
            \DB::commit();
            // send notification sms
            $this->sendAppointmentSms($appointment->customer_phone, $appointment->booking_time, $serviceData);

            // Log the appointment creation by customer
            $this->appointmentLogService->logAppointmentBooked(
                $appointment->id,
                false,
                $appointment->booking_time,
                $serviceData['service_title'],
                $serviceData['customer_name'],
                $serviceData['staff_name'],
                $appointment->comments ?? 'Appointment booked by customer',
            );

            return $appointment->load('services');
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function takeBreakAppointment($data)
    {
        $appointmentData = [
            'type' => 'break',
            'booking_time' => $data['date'] . ' ' . $data['time'],
        ];
        $appointmentServiceData = [
            'booking_time' => $data['date'] . ' ' . $data['time'],
            'service_id' => 0,
            'package_id' => 0,
            'service_title' => $data['service_title'],
            'service_duration' => $data['service_duration'],
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'status' => 'break',
            'customer_name' => $data['staff_name'],
            'comments' => $data['comments'],
        ];
        \DB::beginTransaction();
        try {
            $appointment = $this->createAppointment($appointmentData);
            $appointmentServiceData['appointment_id'] = $appointment->id;
            $this->createServiceAppointment($appointmentServiceData);
            \DB::commit();
            return $appointment->load('services');
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function cancelAppointments($id)
    {
        $appointment = $this->getAppointmentById($id);
        if (!$appointment) {
            throw new \Exception('Appointment not found', 404);
        }
        $appointment->status = 'cancelled';
        $appointment->save();
        $serviceAppointments = $appointment->services->first();

        // Log the appointment cancellation
        $this->appointmentLogService->logAppointmentCancelled(
            $id,
            $serviceAppointments->booking_time,
            $serviceAppointments->service_title,
            $serviceAppointments->customer_name,
            $serviceAppointments->staff_name,
            $serviceAppointments->comments ?? 'Appointment cancelled',
        );
        $serviceAppointments->delete();
        return $appointment;
    }

    public function getBookedServiceByDate($date)
    {
        $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
        $appointments = $this->getAppointmentByDate($dateObj);
        $response = [];
        foreach ($appointments as $appointment) {
            foreach ($appointment->services as $service) {
                $response[] = [
                    'id' => $service->id,
                    'staff_id' => $service->staff_id,
                    'staff_name' => $service->staff_name,
                    'booking_time' => $service->booking_time,
                    'expected_end_time' => \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $service->booking_time)
                        ->addMinutes($service->service_duration),
                    'package_title' => $service->package_title,
                    'service_id' => $service->service_id,
                    'service_title' => $service->service_title,
                    'service_duration' => $service->service_duration,
                    'service_price' => $service->service_price,
                    'comments' => $appointment->comments,
                    'appointment_id' => $appointment->id,
                    'customer_id' => $appointment->customer_id,
                    'customer_name' => $service->customer_name,
                    'customer_first_name' => $appointment->customer_first_name,
                    'customer_last_name' => $appointment->customer_last_name,
                    'customer_phone' => $appointment->customer_phone,
                    'customer_email' => $appointment->customer_email,
                    'tag' => $appointment->tag,
                    'status' => $appointment->status,
                    'type' => $appointment->type,
                    'actual_start_time' => $appointment->actual_start_time,
                    'actual_end_time' => $appointment->actual_end_time,
                ];
            }
        }
        return $response;
    }

    public function updateAppointmentWithService($id, $appointmentData, $inputService, $staff)
    {
        $serviceData = [];
        $appointment = $this->getAppointmentById($id);
        $booking_time = null;
        $log_comment = '';
        if (isset($appointmentData['booking_date']) && isset($appointmentData['booking_time'])) {
            $booking_time = $appointmentData['booking_date'] . ' ' . $appointmentData['booking_time'] . ":00";
            unset($appointmentData['booking_time']);
            unset($appointmentData['booking_date']);
            if ($appointment->booking_time != $booking_time) {
                $appointmentData['booking_time'] = $booking_time;
                $serviceData['booking_time'] = $booking_time;
            }
            $log_comment .= 'Booking time: ' . $booking_time . '. ';
        }
        if (isset($appointmentData['actual_start_time'])) {
            $serviceData['booking_time'] = $appointmentData['actual_start_time'];
        }
        $serviceAppointment = $appointment->services->first();
        if (isset($inputService['id'])) {
            if ($serviceAppointment->id != $inputService['id']) {
                $service = Service::with('package')->findOrFail($inputService['id']);
                $serviceData['service_id'] = $service->id ?? null;
                $serviceData['package_id'] = $service->package_id ?? null;
                $serviceData['package_title'] = $service->package->title ?? null;
                $serviceData['package_hint'] = $service->package->hint ?? null;

                $serviceData['service_title'] = $service->title ?? null;
                $serviceData['service_description'] = $service->description ?? null;
                $serviceData['service_duration'] = $service->duration;
                $serviceData['service_price'] = $service->price;
                $log_comment .= 'Service: ' . $service->title . '. ';
            }
        }
        if (isset($staff['id'])) {
            if ($serviceAppointment->staff_id != $staff['id']) {
                $serviceData['staff_id'] = $staff['id'];
                $serviceData['staff_name'] = $staff['name'];
                $log_comment .= 'Therapist: ' . $staff['name'] . '. ';
            }
        }
        if (isset($appointmentData['customer_name'])) {
            if ($serviceAppointment->customer_name != $appointmentData['customer_name']) {
                $serviceData['customer_name'] = $appointmentData['customer_name'];
                $log_comment .= 'Customer: ' . $appointmentData['customer_name'] . '. ';
            }
        }
        $this->serviceAppointmentService->updateServiceAppointment($serviceAppointment->id, $serviceData);

        //Log the appointment update
        $this->appointmentLogService->logAppointmentUpdated(
            $id,
            $booking_time ?? $appointment->booking_time,
            $inputService['service_title'] ?? $serviceAppointment->service_title,
            $appointmentData['customer_name'] ?? null,
            $staff['name'],
            $log_comment,
        );

        return $this->updateAppointment($id, $appointmentData);
    }


    public function sendSms($data)
    {
        $phone_number = [$data['phone_number']];
        $text = $data['message'];
        $schedule_time = $data['schedule_time'] ?? null;
        if ($data['is_schedule_time']) {
            $smsResponse = $this->smsService->sendSms($text, $phone_number, $schedule_time);
        } else {
            $smsResponse = $this->smsService->sendSms($text, $phone_number);
        }

        $serviceData = [
            'appointment_id' => $data['appointment_id'] ?? null,
            'customer_name' => $data['customer_name'],
            'phone_number' => $data['phone_number'],
            'message' => $data['message'],
        ];
        $this->notificationService->createBookingNotification(
            $smsResponse,
            $serviceData,
            'Appointment Reminder'
        );
        if ($smsResponse->meta->status !== 'SUCCESS') {
            throw new \Exception($smsResponse->msg, 500);
        }
        return $smsResponse->msg;
    }

    public function sendAppointmentSms($phone, $booking_time, $serviceData)
    {
        $booking_reminder_setting = $this->systemSettingService->getSettingByKey('booking_reminder')->value;
        if ($booking_reminder_setting == "true") {
            $smsMassage = $this->systemSettingService->getSettingByKey('booking_reminder_msg')->value;
            $smsMassage = $this->formatMassage($smsMassage, $serviceData);
            $smsResponse = $this->smsService->sendSms(
                $smsMassage,
                [$phone],
            );
            if ($smsResponse) {
                $serviceData['phone_number'] = $phone;
                $this->notificationService->createBookingNotification($smsResponse, $serviceData);
            }
        }

        $reminder_interval = (int) $this->systemSettingService->getSettingByKey('reminder_interval')->value;

        if ($reminder_interval > 0) {
            $schedule_time = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $booking_time);
            $today = \Carbon\Carbon::now()->format('Y-m-d');
            // Skip today
            if ($today == $schedule_time->format('Y-m-d')) {
                return;
            } else {
                $reminder_time = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $booking_time, 'Australia/Sydney')
                    ->subHours($reminder_interval);
                // Skip today's reminder
                if ($today == $reminder_time->format('Y-m-d')) {
                    return;
                }
                if ($reminder_time < \Carbon\Carbon::now('Australia/Sydney')) {
                    return;
                }
                $reminderMassage = $this->systemSettingService->getSettingByKey('reminder_msg')->value;
                $reminderMassage = $this->formatMassage($reminderMassage, $serviceData);
                $smsResponse = $this->smsService->sendSms(
                    $reminderMassage,
                    [$phone],
                    $reminder_time->format('Y-m-d H:i:s')
                );
                if ($smsResponse) {
                    $serviceData['phone_number'] = $phone;
                    $this->notificationService->createBookingNotification($smsResponse, $serviceData, 'Appointment Reminder scheduled');
                }
            }
        }
    }

    public function formatMassage($smsMassage, $serviceData)
    {
        $bookingDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $serviceData['booking_time']);
        $formattedDate = $bookingDateTime->format('l, F j');
        $formattedTime = $bookingDateTime->format('g:i A');
        $names = explode(' ', $serviceData['customer_name'], 2);
        $firstName = $names[0] ?? '';
        $lastName = $names[1] ?? '';
        $smsMassage = str_replace('{first_name}', $firstName, $smsMassage);
        $smsMassage = str_replace('{last_name}', $lastName, $smsMassage);
        $smsMassage = str_replace('{date}', $formattedDate, $smsMassage);
        $smsMassage = str_replace('{time}', $formattedTime, $smsMassage);
        $smsMassage = str_replace('{service}', $serviceData['service_title'] ?? '', $smsMassage);
        $smsMassage = str_replace('{duration}', $serviceData['service_duration'] . ' minutes', $smsMassage);
        $smsMassage = str_replace('{therapist}', $serviceData['any_therapist'] ? 'Any Therapist' : $serviceData['staff_name'], $smsMassage);
        return $smsMassage;
    }

    public function markAppointmentAsNoShow($id)
    {
        $appointment = $this->getAppointmentById($id);
        if (!$appointment) {
            throw new \Exception('Appointment not found', 404);
        }
        $appointment->type = 'no_show';
        $appointment->status = 'pending';
        $appointment->services()->each(function ($service) {
            $service->staff_id = 0;
            $service->staff_name = '';
            $service->save();
        });
        $appointment->save();
        $serviceAppointments = $appointment->services->first();
        // Log the appointment no-show
        $this->appointmentLogService->logAppointmentNoShow(
            $id,
            $appointment->booking_time,
            $serviceAppointments->service_title,
            $serviceAppointments->customer_name,
            $serviceAppointments->staff_name,
            $serviceAppointments->comments ?? 'Appointment marked as no-show',
        );

        return $appointment;
    }

    public function getTodayStatistics()
    {
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        //Test date 02/06/2025
        // $today = \Carbon\Carbon::createFromFormat('Y-m-d', '2025-07-10');
        //Fetch the current week appointments
        $weekBegin = \Carbon\Carbon::parse($today)->startOfWeek();
        $weekEnd = \Carbon\Carbon::parse($today)->endOfWeek();

        $weekAppointments = $this->appointmentRepository->getStatisticsByDate($weekBegin, $weekEnd);
        //Group appointments count of every day in the week
        $weekAppointmentsCount = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekBegin->copy()->addDays($i)->format('Y-m-d');
            $weekAppointmentsCount[] = $weekAppointments->filter(function ($appointment) use ($date) {
                return \Carbon\Carbon::parse($appointment->booking_time)->format('Y-m-d') === $date;
            })->count();
        }

        $appointments = $this->appointmentRepository->getStatisticsByDate($today, $today);

        if ($appointments->isEmpty()) {
            return [
                'weekAppointmentsCount' => $weekAppointmentsCount,
            ];
            return [];
        }

        $totalAppointments = $appointments->count();
        $appointmentGroupedByStatus = [];
        foreach ($appointments as $appointment) {
            $status = $appointment->status;
            if (!isset($appointmentGroupedByStatus[$status])) {
                $appointmentGroupedByStatus[$status] = 0;
            }
            $appointmentGroupedByStatus[$status]++;
        }
        // Get orders from appointments
        $orders = $appointments->pluck('order');

        $totalAmount = $orders->sum('total_amount');
        $paidAmount = $orders->sum('paid_amount');

        $amountGroupedByPaymentMethod = [];
        foreach ($orders as $order) {
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
            'weekAppointmentsCount' => $weekAppointmentsCount,
            'total_appointments' => $totalAppointments,
            'total_revenue' => $totalAmount,
            'total_paid' => $paidAmount,
            'appointmentGroup' => $appointmentGroupedByStatus,
            'orderGroup' => $amountGroupedByPaymentMethod,
            'appointments' => $appointments,
            'orders' => $orders,
        ];
    }

    public function getTotalStatistics($beginDate, $endDate)
    {
        $begin = \Carbon\Carbon::createFromFormat('Y-m-d', $beginDate);
        $end = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate);

        $appointments = $this->appointmentRepository->getStatisticsByDate($begin, $end);
        if ($appointments->isEmpty()) {
            return [
            ];
        }
        $totalAppointments = $appointments->count();
        $orders = $appointments->pluck('order');
        $totalAmount = $orders->sum('total_amount');
        $paidAmount = $orders->sum('paid_amount');
        $voucherAmount = $orders->sum('voucher_amount');

        return [
            'total_appointments' => $totalAppointments,
            'total_revenue' => $totalAmount,
            'total_paid' => $paidAmount,
            'total_voucher' => $voucherAmount,
        ];
    }
}
