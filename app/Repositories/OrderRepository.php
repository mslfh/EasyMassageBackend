<?php

namespace App\Repositories;

use App\Contracts\OrderContract;
use App\Models\Order;

class OrderRepository implements OrderContract
{
    public function getAllOrders()
    {
        return Order::all()->with('payment');
    }

    public function getOrderByAppointment($appointmentId)
    {
        return Order::where('appointment_id', '=', $appointmentId)
        ->first();
    }

    public function getOrderById($id)
    {
        return Order::where('id','=',$id)
        ->with('payment')
        ->with('appointment.services')
        ->first();
    }

    public function createOrder(array $data)
    {
        $order = Order::create($data);
        if(isset($data['payment'])) {
            foreach ($data['payment'] as $key => $value) {
                $data['payment'][$key]['order_id'] = $order->id;
            }
            $order->payment()->createMany($data['payment']);
        }
        return $order;
    }

    public function updateOrder($id, array $data)
    {
        $order = Order::findOrFail($id);
        $order->update($data);
        if(isset($data['payment'])) {
            foreach ($data['payment'] as $key => $value) {
                $data['payment'][$key]['order_id'] = $order->id;
            }
            $order->payment()->delete();
            $order->payment()->createMany($data['payment']);
        }
        else{
            $order->payment()->delete();
        }
        return $order;
    }

    public function deleteOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return $order;
    }

    public function getOrdersByDateRange($startDate, $endDate = null)
    {
        return Order::whereHas('appointment', function ($query) use ($startDate, $endDate) {
                $query->whereDate('booking_time', '>=', $startDate);
                if ($endDate) {
                    $query->whereDate('booking_time', '<=', $endDate);
                }
            })
            ->with('payment')
            ->with('appointment.services', function ($query) {
                $query->withTrashed();
            })
            ->get();
    }
}
