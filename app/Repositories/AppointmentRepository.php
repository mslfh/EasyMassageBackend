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
        )->with('services')->with('order')->orderBy('booking_time')->get();
    }

    public function getStatisticsByDate($beginDate, $endDate= null)
    {
        $query = Appointment::whereDate('booking_time', '>=', $beginDate);
        if ($endDate) {
            $query->whereDate('booking_time', '<=', $endDate);
        }
        return $query->whereNotIn(
            'type',
            ['break','no_show']
        )
        ->with('order.payment')->get();
    }

    public function getUserBookingHistory($userId, $phone = null)
    {
        if ($phone) {
            return Appointment::where('customer_phone', $phone)
            ->with('services', function ($query) {
                $query->withTrashed();
            })->orderBy('booking_time','desc')->get();
        }
        return Appointment::where(
            'customer_id', $userId
        )
        ->with('services', function ($query) {
            $query->withTrashed();
        })->orderBy('booking_time','desc')->get();
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

    public function getServiceAppointments($id)
    {
        return Appointment::findOrFail($id)->services;
    }

    public function delete($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->services()->delete();
        $appointment->delete();
        return $appointment;
    }
}
