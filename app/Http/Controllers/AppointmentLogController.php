<?php

namespace App\Http\Controllers;

use App\Services\AppointmentLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentLogController extends Controller
{
    protected $appointmentLogService;

    public function __construct(AppointmentLogService $appointmentLogService)
    {
        $this->appointmentLogService = $appointmentLogService;
    }

    /**
     * Get all appointment logs with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $logs = $this->appointmentLogService->getAllLogs($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get logs for a specific appointment
     */
    public function getAppointmentLogs($appointmentId): JsonResponse
    {
        $logs = $this->appointmentLogService->getAppointmentLogs($appointmentId);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get appointment timeline
     */
    public function getAppointmentTimeline($appointmentId): JsonResponse
    {
        $timeline = $this->appointmentLogService->getAppointmentTimeline($appointmentId);

        return response()->json([
            'success' => true,
            'data' => $timeline
        ]);
    }

    /**
     * Get logs by date range
     */
    public function getLogsByDateRange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $logs = $this->appointmentLogService->getLogsByDateRange(
            $data['start_date'],
            $data['end_date'] ?? now()->toDateString()
        );

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get successful logs
     */
    public function getSuccessfulLogs(Request $request): JsonResponse
    {
        $appointmentId = $request->get('appointment_id');
        $logs = $this->appointmentLogService->getSuccessfulLogs($appointmentId);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get failed logs
     */
    public function getFailedLogs(Request $request): JsonResponse
    {
        $appointmentId = $request->get('appointment_id');
        $logs = $this->appointmentLogService->getFailedLogs($appointmentId);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get recent logs
     */
    public function getRecentLogs(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $logs = $this->appointmentLogService->getRecentLogs($hours);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

}
