<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    protected $notificationService;
    protected $smsService;

    public function __construct(NotificationService $notificationService,SmsService $smsService)
    {
        $this->notificationService = $notificationService;
        $this->smsService = $smsService;
    }

    public function index()
    {
        return response()->json($this->notificationService->getAllNotifications());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no' => 'required|string|unique:notifications,no',
            'appointment_id' => 'nullable|exists:appointments,id',
            'type' => 'nullable|string',
            'recipient_name' => 'required|string',
            'recipient_email' => 'required|email',
            'recipient_phone' => 'required|string',
            'subject' => 'required|string',
            'content' => 'required|string',
            'status' => 'nullable|string',
            'schedule_time' => 'nullable|date',
            'error_message' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);
        return response()->json($this->notificationService->createNotification($data), 201);
    }

    public function show($id)
    {
        return response()->json($this->notificationService->getNotificationById($id));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'type' => 'nullable|string',
            'recipient_name' => 'nullable|string',
            'recipient_email' => 'nullable|email',
            'recipient_phone' => 'nullable|string',
            'subject' => 'nullable|string',
            'content' => 'nullable|string',
            'status' => 'nullable|string',
            'schedule_time' => 'nullable|date',
            'error_message' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);
        return response()->json($this->notificationService->updateNotification($id, $data));
    }

    public function destroy($id)
    {
        $this->notificationService->deleteNotification($id);
        return response()->json(null, 204);
    }

    public function getNotification(Request $request )
    {
        $beginDate = $request->input('begin_date');
        $endDate = $request->input('end_date');

        return response()->json(
            $this->notificationService->getNotificationByDateRange($beginDate, $endDate)
        );
    }

    public function getSmsBalance(Request $request)
    {
        return response()->json(
            $this->smsService->getBalance()
        );
    }

}
