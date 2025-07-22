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
        $query = AppointmentLog::with('appointment')
            ->whereDate('created_at', '>=', $startDate);

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

    public function createLog($appointmentId, $status, $description)
    {
        return $this->create([
            'appointment_id' => $appointmentId,
            'status' => $status,
            'description' => $description,
        ]);
    }

    public function logAppointmentBooked($appointmentId, $bookedByStaff = false, $serviceTitle = null, $staffName = null)
    {
        $description = $bookedByStaff
            ? AppointmentLog::DESC_APPOINTMENT_BOOKED_BY_STAFF
            : AppointmentLog::DESC_APPOINTMENT_BOOKED;

        if ($serviceTitle) {
            $description .= ' - Service: ' . $serviceTitle;
        }

        if ($staffName) {
            $description .= ' - Staff: ' . $staffName;
        }

        return $this->createLog($appointmentId, AppointmentLog::STATUS_SUCCESS, $description);
    }

    public function logAppointmentUpdated($appointmentId, $serviceTitle = null, $staffName = null)
    {
        $description = AppointmentLog::DESC_APPOINTMENT_UPDATED;
        if ($serviceTitle) {
            $description .= ' - Service: ' . $serviceTitle;
        }
        if ($staffName) {
            $description .= ' - Staff: ' . $staffName;
        }
        return $this->createLog($appointmentId, AppointmentLog::STATUS_SUCCESS, AppointmentLog::DESC_APPOINTMENT_UPDATED);
    }

    public function logAppointmentCancelled($appointmentId)
    {
        return $this->createLog($appointmentId, AppointmentLog::STATUS_SUCCESS, AppointmentLog::DESC_APPOINTMENT_CANCELLED);
    }

    public function logAppointmentDeleted($appointmentId)
    {
        return $this->createLog($appointmentId, AppointmentLog::STATUS_SUCCESS, AppointmentLog::DESC_APPOINTMENT_DELETED);
    }

    public function logCheckedOut($appointmentId, $paidAmount = 0, $paymentMethod = 'unpaid', $paymentNote = '', $voucherCode = null)
    {
        $description = AppointmentLog::DESC_CHECKED_OUT;
        if ($paidAmount > 0) {
            $description .= ' - Paid Amount: ' . $paidAmount;
        }
        if ($paymentMethod) {
            $description .= ' - Payment Method: ' . $paymentMethod;
        }
        if ($paymentNote) {
            $description .= ' - Payment Note: ' . $paymentNote;
        }
        if ($voucherCode) {
            $description .= ' - Voucher Code: ' . $voucherCode;
        }

        return $this->createLog($appointmentId, AppointmentLog::STATUS_SUCCESS, $description);
    }

    public function logMessageSent($appointmentId, $success = true, $subject = null)
    {
        $status = $success ? AppointmentLog::STATUS_SUCCESS : AppointmentLog::STATUS_FAILED;

        $description = $subject ? "Message sent: $subject" : "Message sent";

        return $this->createLog($appointmentId, $status, $description);
    }


    public function logAppointmentNoShow($appointmentId)
    {
        return $this->createLog($appointmentId, AppointmentLog::STATUS_SUCCESS, AppointmentLog::DESC_APPOINTMENT_NO_SHOW);
    }
}
