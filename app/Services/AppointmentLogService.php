<?php

namespace App\Services;

use App\Contracts\AppointmentLogContract;
use App\Models\AppointmentLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentLogService
{
    protected $appointmentLogRepository;

    public function __construct(AppointmentLogContract $appointmentLogRepository)
    {
        $this->appointmentLogRepository = $appointmentLogRepository;
    }

    /**
     * Get all appointment logs with pagination support
     */
    public function getAllLogs($perPage = 15)
    {
        return AppointmentLog::with('appointment')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get logs for a specific appointment
     */
    public function getAppointmentLogs($appointmentId)
    {
        return $this->appointmentLogRepository->getByAppointmentId($appointmentId);
    }

    /**
     * Get logs by date range
     */
    public function getLogsByDateRange($startDate, $endDate = null)
    {
        return $this->appointmentLogRepository->getLogsByDateRange($startDate, $endDate);
    }

    /**
     * Get successful logs
     */
    public function getSuccessfulLogs($appointmentId = null)
    {
        return $this->appointmentLogRepository->getSuccessfulLogs($appointmentId);
    }

    /**
     * Get failed logs
     */
    public function getFailedLogs($appointmentId = null)
    {
        return $this->appointmentLogRepository->getFailedLogs($appointmentId);
    }

    /**
     * Create a general log entry
     */
    public function createLog($appointmentId, $status, $description)
    {
        try {
            return $this->appointmentLogRepository->createLog($appointmentId, $status, $description);
        } catch (\Exception $e) {
            Log::error('Failed to create appointment log', [
                'appointment_id' => $appointmentId,
                'status' => $status,
                'description' => $description,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Log when an appointment is booked
     */
    public function logAppointmentBooked($appointmentId, $bookedByStaff = false, $serviceTitle = null, $staffName = null)
    {
        return $this->appointmentLogRepository->logAppointmentBooked($appointmentId, $bookedByStaff, $serviceTitle, $staffName);
    }

    /**
     * Log when an appointment is updated
     */
    public function logAppointmentUpdated($appointmentId, $serviceTitle = null, $staffName = null)
    {
        return $this->appointmentLogRepository->logAppointmentUpdated($appointmentId, $serviceTitle, $staffName);
    }

    /**
     * Log when an appointment is cancelled
     */
    public function logAppointmentCancelled($appointmentId)
    {
        return $this->appointmentLogRepository->logAppointmentCancelled($appointmentId);
    }

    /**
     * Log when an appointment is deleted
     */
    public function logAppointmentDeleted($appointmentId)
    {
        return $this->appointmentLogRepository->logAppointmentDeleted($appointmentId);
    }

    /**
     * Log when a customer is checked out
     */
    public function logCheckedOut($appointmentId, $paidAmount = 0, $paymentMethod = 'unpaid', $paymentNote = '', $voucherCode = null)
    {
        return $this->appointmentLogRepository->logCheckedOut($appointmentId, $paidAmount, $paymentMethod, $paymentNote, $voucherCode);
    }

    /**
     * Log when message is sent
     */
    public function logMessageSent($appointmentId, $success = true, $subject = null)
    {
        return $this->appointmentLogRepository->logMessageSent($appointmentId, $success, $subject);
    }

    /**
     * Log when customer is waiting for service
     */
    public function logAppointmentNoShow($appointmentId)
    {
        return $this->appointmentLogRepository->logAppointmentNoShow($appointmentId);
    }

    /**
     * Get appointment timeline (all logs for an appointment)
     */
    public function getAppointmentTimeline($appointmentId)
    {
        return $this->getAppointmentLogs($appointmentId)->map(function ($log) {
            return [
                'id' => $log->id,
                'status' => $log->status,
                'description' => $log->description,
                'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                'human_time' => $log->created_at->diffForHumans(),
            ];
        });
    }

    /**
     * Get statistics about appointment logs
     */
    public function getLogStatistics($startDate = null, $endDate = null)
    {
        $query = AppointmentLog::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $totalLogs = $query->count();
        $successfulLogs = (clone $query)->where('status', AppointmentLog::STATUS_SUCCESS)->count();
        $failedLogs = (clone $query)->where('status', AppointmentLog::STATUS_FAILED)->count();

        // Get logs by description
        $logsByType = $query->select('description', DB::raw('count(*) as count'))
            ->groupBy('description')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'total_logs' => $totalLogs,
            'successful_logs' => $successfulLogs,
            'failed_logs' => $failedLogs,
            'success_rate' => $totalLogs > 0 ? round(($successfulLogs / $totalLogs) * 100, 2) : 0,
            'logs_by_type' => $logsByType,
        ];
    }

    /**
     * Bulk create logs for multiple appointments
     */
    public function bulkCreateLogs(array $logs)
    {
        try {
            DB::beginTransaction();

            $createdLogs = [];
            foreach ($logs as $logData) {
                $createdLogs[] = $this->appointmentLogRepository->create($logData);
            }

            DB::commit();
            return $createdLogs;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk create appointment logs', [
                'logs' => $logs,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get recent logs (last 24 hours by default)
     */
    public function getRecentLogs($hours = 24)
    {
        $startTime = Carbon::now()->subHours($hours);

        return AppointmentLog::with('appointment')
            ->where('created_at', '>=', $startTime)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up old logs (older than specified days)
     */
    public function cleanupOldLogs($daysToKeep = 365)
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        try {
            $deletedCount = AppointmentLog::where('created_at', '<', $cutoffDate)->delete();

            Log::info("Cleaned up old appointment logs", [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old appointment logs', [
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
