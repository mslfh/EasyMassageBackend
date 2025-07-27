<?php

namespace App\Services;

use App\Contracts\NotificationContract;

class NotificationService
{
    protected $notificationRepository;

     protected $appointmentLogService;

    public function __construct(NotificationContract $notificationRepository, AppointmentLogService $appointmentLogService)
    {
        $this->notificationRepository = $notificationRepository;
        $this->appointmentLogService = $appointmentLogService;
    }

    public function getAllNotifications()
    {
        return $this->notificationRepository->getAll();
    }

    public function getNotificationById($id)
    {
        return $this->notificationRepository->getById($id);
    }

    public function createNotification(array $data)
    {
        return $this->notificationRepository->create($data);
    }

    public function createBookingNotification($smsResponse, array $serviceData, $subject = 'Booking Confirmation')
    {
        if($smsResponse->meta->status !== 'SUCCESS') {
            $data = [
                'no' => null,
                'appointment_id' => $serviceData['appointment_id']?? null,
                'type' => 'SMS',
                'recipient_name' => $serviceData['customer_name'],
                'recipient_phone' => $serviceData['phone_number'],
                'subject' => $subject,
                'content' => $smsResponse->msg,
                'status' => $smsResponse->meta->status === 'SUCCESS' ? 'sent' : 'failed',
                'schedule_time' => null,
                'error_message' => $smsResponse->msg,
            ];
        }
        else{
            $data = [
                'no' => $smsResponse->data->messages[0]->message_id,
                'appointment_id' => $serviceData['appointment_id']?? null,
                'type' => 'SMS',
                'recipient_name' => $serviceData['customer_name'],
                'recipient_phone' => $serviceData['phone_number'],
                'subject' => $subject,
                'content' => $smsResponse->data->messages[0]->body,
                'status' => $smsResponse->meta->status === 'SUCCESS' ? 'sent' : 'failed',
                'schedule_time' => $smsResponse->data->messages[0]->schedule_time??$smsResponse->data->messages[0]->date,
                'error_message' => $smsResponse->meta->status !== 'SUCCESS' ? $smsResponse->msg : null,
            ];
        }

        // Log the notification creation
        $this->appointmentLogService->logMessageSent($serviceData['appointment_id'],
            $smsResponse->meta->status === 'SUCCESS',$subject, $serviceData['customer_name']);

        return $this->notificationRepository->create($data);
    }

    public function updateNotification($id, array $data)
    {
        return $this->notificationRepository->update($id, $data);
    }

    public function deleteNotification($id)
    {
        return $this->notificationRepository->delete($id);
    }

    public function getNotificationByDateRange($beginDate, $endDate)
    {
        return $this->notificationRepository->getNotificationByDateRange($beginDate, $endDate);
    }
}
