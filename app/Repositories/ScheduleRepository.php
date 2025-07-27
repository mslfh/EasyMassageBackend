<?php

namespace App\Repositories;

use App\Contracts\ScheduleContract;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleRepository implements ScheduleContract
{
    public function getAllSchedules()
    {
        return Schedule::with('staff')->get();
    }

    public function getAllAvailableSchedules()
    {
        $today = Carbon::today();
        return Schedule::whereDate('work_date', '>=', $today)
            ->where('status', 'active')
            ->get();
    }

    public function getAvailableScheduleByDate($date)
    {
        return Schedule::whereDate('work_date', $date)
            ->where('status', 'active')
            ->get();
    }

    public function getScheduleById($id)
    {
        return Schedule::findOrFail($id);
    }

    public function createSchedule(array $data)
    {
        return Schedule::create($data);
    }

    public function updateSchedule($id, array $data)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update($data);
        return $schedule;
    }

    public function deleteSchedule($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        return $schedule;
    }

    public function getSchedulesByDate($date)
    {
        return Schedule::whereDate('work_date', $date)
            ->where('status', 'active')
            ->with('staff')
            ->get();
    }

     public function getSchedulesFromDateAndStaff($data)
    {
        $query = Schedule::query();
        if (isset($data['staff_id'])) {
            $query->where('staff_id', $data['staff_id']);
        }
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $query->whereDate('work_date', '>=', $data['start_date'])
                ->whereDate('work_date', '<=', $data['end_date']);
        }
        return $query->with('staff')->get();
    }

    public function getStaffScheduleStatistics()
    {
        return Schedule::select('staff_id', \DB::raw('count(*) as total_schedules'))
            ->groupBy('staff_id')
            ->with('staff')
            ->get();
    }
}
