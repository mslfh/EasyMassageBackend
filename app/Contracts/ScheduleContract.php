<?php

namespace App\Contracts;

interface ScheduleContract
{
    public function getAllSchedules();
    public function getSchedulesFromDateAndStaff($data);
    public function getAllAvailableSchedules();
    public function getAvailableScheduleByDate($date);
    public function getScheduleById($id);
    public function createSchedule(array $data);
    public function updateSchedule($id, array $data);
    public function deleteSchedule($id);
    public function getSchedulesByDate($date);
    public function getStaffScheduleStatistics();
}
