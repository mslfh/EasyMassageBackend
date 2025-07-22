<?php

namespace App\Repositories;

use App\Contracts\NotificationContract;
use App\Models\Notification;

class NotificationRepository implements NotificationContract
{
    public function getAll()
    {
        return Notification::all();
    }

    public function getById($id)
    {
        return Notification::findOrFail($id);
    }

    public function create(array $data)
    {
        return Notification::create($data);
    }

    public function update($id, array $data)
    {
        $notification = Notification::findOrFail($id);
        $notification->update($data);
        return $notification;
    }

    public function delete($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();
        return true;
    }

    public function getNotificationByDateRange($beginDate, $endDate)
    {
        return Notification::select('id','appointment_id', 'recipient_name', 'recipient_phone', 'subject', 'content', 'status', 'schedule_time')
            ->whereDate('created_at', '>=', $beginDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();
    }
}
