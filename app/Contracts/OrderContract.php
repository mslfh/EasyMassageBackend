<?php

namespace App\Contracts;

interface OrderContract
{
    public function getAllOrders();
    public function getOrderById($id);
    public function getOrderByAppointment($appointmentId);
    public function createOrder(array $data);
    public function updateOrder($id, array $data);
    public function deleteOrder($id);
    public function getOrdersByDateRange($startDate, $endDate = null);
}
