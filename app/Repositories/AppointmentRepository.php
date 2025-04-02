<?php

namespace App\Repositories;

use App\Contracts\AppointmentContract;
use App\Models\Appointment;

class AppointmentRepository implements AppointmentContract
{
    public function getAll()
    {
        return Appointment::all()->load('services');
    }
    public function getByDate($date)
    {
        return Appointment::whereDate('booking_time', $date)
        ->whereNot(
            'status',
            'cancelled'
        )->with('services')->orderBy('booking_time')->get();
    }

    public function getById($id)
    {
        return Appointment::findOrFail($id)->load('services');
    }

    public function create(array $data)
    {
        return Appointment::create($data);
    }

    public function update($id, array $data)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update($data);
        return $appointment;
    }

    public function delete($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return $appointment;
    }
}
