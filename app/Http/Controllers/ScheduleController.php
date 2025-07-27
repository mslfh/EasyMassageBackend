<?php

namespace App\Http\Controllers;

use App\Services\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends BaseController
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function index()
    {
        return response()->json($this->scheduleService->getAllSchedules());
    }

    public function getStaffSchedule(Request $request)
    {
        $data = $request->validate([
            'staff_id' => 'nullable|integer',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);
        return response()->json($this->scheduleService->getSchedulesFromDateAndStaff($data));
    }

    public function show($id)
    {
        return response()->json($this->scheduleService->getScheduleById($id));
    }

    public function getUnavailableTimeFromDate(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d']);
        $result = $this->scheduleService->getUnavailableTimeFromDate($data['date']);
        return response()->json($result);
    }

    public function getUnavailableTimeFromStaff(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'staff_id' => 'required|integer',
        ]);
        $result = $this->scheduleService->getUnavailableTimeFromStaff($data['date'], $data['staff_id']);
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id' => 'required',
            'work_date' => 'required',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'break_start_time' => 'nullable|string',
            'break_end_time' => 'nullable|string',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);
        $this->scheduleService->createSchedule($data);
        return response()->json(['message' => 'Schedule created successfully']);
    }

    public function insert(Request $request)
    {
        $data = $request->validate([
            'staff_id' => 'required|integer',
            'schedule_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'week_days' => 'required|array',
            'week_days.*.day' => 'required|string',
            'week_days.*.start_time' => 'required|string',
            'week_days.*.end_time' => 'required|string',
            'week_days.*.additional_times' => 'nullable|array',
            'week_days.*.additional_times.*.start' => 'required_with:week_days.*.additional_times|string',
            'week_days.*.additional_times.*.end' => 'required_with:week_days.*.additional_times|string',
        ]);
        $this->scheduleService->insertSchedules($data);
        return response()->json(['message' => 'Schedule created successfully']);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'staff_id' => 'sometimes|exists:staff,id',
            'work_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'break_start_time' => 'nullable|string',
            'break_end_time' => 'nullable|string',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);
        return response()->json($this->scheduleService->updateSchedule($id, $data));
    }

    public function destroy($id)
    {
        $this->scheduleService->deleteSchedule($id);
        return response()->json(null, 204);
    }

    public function getAvailableSchedules()
    {
        $result = $this->scheduleService->getAvailableSchedules();
        return response()->json($result);
    }

    public function getStaffScheduleStatistics()
    {
        dd('This endpoint is deprecated. Please use the new endpoint for staff schedule statistics.');
        $result = $this->scheduleService->getStaffScheduleStatistics();
        return response()->json($result);
    }
}
