<?php

namespace App\Contracts;

interface ServiceAppointmentContract
{
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function findById(int $id);
    public function getAll();
    public function getAppointmentsFromDate($date);
    public function getAppointmentsFromDateRange($startDate, $endDate);
}
