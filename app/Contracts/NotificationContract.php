<?php

namespace App\Contracts;

interface NotificationContract
{
    public function getAll();
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getNotificationByDateRange($beginDate, $endDate);
}
