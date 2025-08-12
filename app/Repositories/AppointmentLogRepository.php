<?php

namespace App\Repositories;

use App\Contracts\AppointmentLogContract;
use App\Models\AppointmentLog;

class AppointmentLogRepository implements AppointmentLogContract
{
    public function getAll()
    {
        return AppointmentLog::with('appointment')->orderBy('created_at', 'desc')->get();
    }

    public function getById($id)
    {
        return AppointmentLog::with('appointment')->findOrFail($id);
    }

    public function getByAppointmentId($appointmentId)
    {
        return AppointmentLog::where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLogsByDateRange($startDate, $endDate = null)
    {
        $query = AppointmentLog::whereDate('created_at', '>=', $startDate);

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getSuccessfulLogs($appointmentId = null)
    {
        $query = AppointmentLog::successful()->with('appointment');

        if ($appointmentId) {
            $query->forAppointment($appointmentId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getFailedLogs($appointmentId = null)
    {
        $query = AppointmentLog::failed()->with('appointment');

        if ($appointmentId) {
            $query->forAppointment($appointmentId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function create(array $data)
    {
        return AppointmentLog::create($data);
    }

    public function update($id, array $data)
    {
        $log = $this->getById($id);
        $log->update($data);
        return $log;
    }

    public function delete($id)
    {
        $log = $this->getById($id);
        return $log->delete();
    }

    public function createLog($appointmentId, $action, $status, $description, $bookingTime = null, $serviceTitle = null, $customerName = null, $comments = null, $staffName = null)
    {
        return $this->create([
            'appointment_id' => $appointmentId,
            'action' => $action,
            'status' => $status,
            'description' => $description,
            'booking_time' => $bookingTime,
            'service_title' => $serviceTitle,
            'customer_name' => $customerName,
            'comments' => $comments,
            'staff_name' => $staffName,
        ]);
    }

    public function logAppointmentBooked($appointmentId, $bookedByStaff = false, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null)
    {
        $description = $bookedByStaff
            ? AppointmentLog::DESC_APPOINTMENT_BOOKED_BY_STAFF
            : AppointmentLog::DESC_APPOINTMENT_BOOKED;

        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_BOOKED,
            AppointmentLog::STATUS_SUCCESS,
            $description,
            $bookingTime,
            $serviceTitle,
            $customerName,
            $comments,
            $staffName
        );
    }

    public function logAppointmentUpdated($appointmentId, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null)
    {
        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_UPDATED,
            AppointmentLog::STATUS_SUCCESS,
            AppointmentLog::DESC_APPOINTMENT_UPDATED,
            $bookingTime,
            $serviceTitle,
            $customerName,
            $comments,
            $staffName
        );
    }

    public function logAppointmentCancelled($appointmentId, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null)
    {
        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_CANCELLED,
            AppointmentLog::STATUS_SUCCESS,
            AppointmentLog::DESC_APPOINTMENT_CANCELLED,
            $bookingTime,
            $serviceTitle,
            $customerName,
            $comments,
            $staffName
        );
    }

    public function logAppointmentDeleted($appointmentId, $customerName = null, $comments = null)
    {
        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_DELETED,
            AppointmentLog::STATUS_SUCCESS,
            AppointmentLog::DESC_APPOINTMENT_DELETED,
            null,
            null,
            $customerName,
            $comments,
            null
        );
    }

    public function logCheckedOut($appointmentId, $paidAmount = 0, $paymentMethod = 'unpaid', $paymentNote = '', $voucherCode = null, $customerName = null, $serviceTitle = null)
    {
        $description = AppointmentLog::DESC_CHECKED_OUT;
        $comments = '';
        if ($paidAmount >= 0) {
            $comments .= 'Paid Amount: ' . $paidAmount;
        }
        if ($paymentMethod) {
            $comments .= ' ; Payment Method: ' . $paymentMethod;
        }
        if ($paymentNote) {
            $comments .= ' ; Payment Note: ' . $paymentNote;
        }
        if ($voucherCode) {
            $comments .= ' ; Voucher Code: ' . $voucherCode;
        }

        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_CHECKED_OUT,
            AppointmentLog::STATUS_SUCCESS,
            $description,
            null,
            $serviceTitle,
            $customerName,
            $comments,
            null
        );
    }

    public function logMessageSent($appointmentId, $success = true, $subject = null, $customerName = null)
    {
        $status = $success ? AppointmentLog::STATUS_SUCCESS : AppointmentLog::STATUS_FAILED;

        $description = $subject ? "Message: $subject" : "Message Sent";

        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_MESSAGE_SENT,
            $status,
            $description,
            null,
            null,
            $customerName,
            null,
            null
        );
    }

    public function logAppointmentNoShow($appointmentId, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null)
    {
        return $this->createLog(
            $appointmentId,
            AppointmentLog::ACTION_UPDATED,
            AppointmentLog::STATUS_SUCCESS,
            AppointmentLog::DESC_APPOINTMENT_NO_SHOW,
            $bookingTime,
            $serviceTitle,
            $customerName,
            $comments,
            $staffName
        );
    }
}
