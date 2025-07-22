<?php

namespace App\Contracts;

interface AppointmentLogContract
{
    public function getAll();
    public function getById($id);
    public function getByAppointmentId($appointmentId);
    public function getLogsByDateRange($startDate, $endDate = null);
    public function getSuccessfulLogs($appointmentId = null);
    public function getFailedLogs($appointmentId = null);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function createLog($appointmentId, $status, $description);
    public function logAppointmentBooked($appointmentId, $bookedByStaff = false, $serviceTitle = null, $staffName = null);
    public function logAppointmentUpdated($appointmentId, $serviceTitle = null, $staffName = null);
    public function logAppointmentCancelled($appointmentId);
    public function logAppointmentDeleted($appointmentId);
    public function logCheckedOut($appointmentId, $paidAmount = 0, $paymentMethod = 'unpaid', $paymentNote = '', $voucherCode = null);
    public function logMessageSent($appointmentId, $success = true, $subject = null);
    public function logAppointmentNoShow($appointmentId);
}
