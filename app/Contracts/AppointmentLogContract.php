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
    public function createLog($appointmentId, $status, $description, $bookingTime = null, $serviceTitle = null, $customerName = null, $comments = null, $staffName = null);
    public function logAppointmentBooked($appointmentId, $bookedByStaff = false, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null);
    public function logAppointmentUpdated($appointmentId, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null);
    public function logAppointmentCancelled($appointmentId, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null);
    public function logAppointmentDeleted($appointmentId, $customerName = null, $comments = null);
    public function logCheckedOut($appointmentId, $paidAmount = 0, $paymentMethod = 'unpaid', $paymentNote = '', $voucherCode = null, $customerName = null, $serviceTitle = null);
    public function logMessageSent($appointmentId, $success = true, $subject = null, $customerName = null);
    public function logAppointmentNoShow($appointmentId, $bookingTime = null, $serviceTitle = null, $customerName = null, $staffName = null, $comments = null);
}
