<?php
namespace App\Services;

use App\Contracts\ScheduleContract;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Services\ServiceAppointmentService;
use App\Services\StaffService;
use App\Services\AppointmentService;

class ScheduleService
{
    protected $scheduleRepository;
    protected $serviceAppointmentService;
    protected $staffService;


    public function __construct(
        ScheduleContract $scheduleRepository,
        ServiceAppointmentService $serviceAppointmentService,
        StaffService $staffService

    ) {
        $this->scheduleRepository = $scheduleRepository;
        $this->serviceAppointmentService = $serviceAppointmentService;
        $this->staffService = $staffService;
    }

    public function getAllSchedules()
    {
        return $this->scheduleRepository->getAllSchedules();
    }

    public function getSchedulesFromDateAndStaff($data)
    {
        return $this->scheduleRepository->getSchedulesFromDateAndStaff($data);
    }

    public function getAllAvailableSchedules()
    {
        return $this->scheduleRepository->getAllAvailableSchedules();
    }

    public function getAvailableScheduleByDate($date)
    {
        return $this->scheduleRepository->getAvailableScheduleByDate($date);
    }

    public function getScheduleById($id)
    {
        return $this->scheduleRepository->getScheduleById($id);
    }

    public function createSchedule(array $data)
    {
        return $this->scheduleRepository->createSchedule($data);
    }

    public function insertSchedules(array $data)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $data['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $data['end_date']);
        $weekDays = collect($data['week_days']);

        for ($currentDate = $startDate; $currentDate->lte($endDate); $currentDate->addDay()) {
            $dayName = $currentDate->format('l');

            $matchingDay = $weekDays->firstWhere('day', $dayName);
            if ($matchingDay) {
                $this->createSchedule([
                    'staff_id' => $data['staff_id'],
                    'work_date' => $currentDate->format('Y-m-d'),
                    'start_time' => $matchingDay['start_time'],
                    'end_time' => $matchingDay['end_time'],
                    'status' => 'active',
                ]);

                if (!empty($matchingDay['additional_times'])) {
                    foreach ($matchingDay['additional_times'] as $additionalTime) {
                        $this->createSchedule([
                            'staff_id' => $data['staff_id'],
                            'work_date' => $currentDate->format('Y-m-d'),
                            'start_time' => $additionalTime['start'],
                            'end_time' => $additionalTime['end'],
                            'status' => 'active',
                        ]);
                    }
                }
            }
        }

    }

    public function updateSchedule($id, array $data)
    {
        return $this->scheduleRepository->updateSchedule($id, $data);
    }

    public function deleteSchedule($id)
    {
        return $this->scheduleRepository->deleteSchedule($id);
    }

    /**
     * Get unavailable time for a specific date
     *
     * @param string $date
     * @return array
     */
    public function getUnavailableTimeFromDate($date)
    {
        $allStaffSchedules = $this->staffService->getStaffScheduleFromDate($date);
        $formatDate = Carbon::createFromFormat('Y-m-d', $date);
        $allSchedules = $allStaffSchedules->pluck('schedules')->flatten();
        if ($allSchedules->isEmpty()) {
            return $this->noScheduleTimeResponse();
        }

        $minScheduleTime = $allSchedules->min('start_time');
        $maxScheduleTime = $allSchedules->max('end_time');
        $unavailableTime = [
            'start_time' => $minScheduleTime,
            'end_time' => $maxScheduleTime,
            'unavailable_time' => [],
        ];

        if ($formatDate->isToday()) {
            $todaySchedule = $this->getAvailableScheduleByDate($formatDate->format('Y-m-d'));
            if ($todaySchedule->isEmpty()) {
                $unavailableTime['unavailable_time'][] = [
                    'start_time' => $minScheduleTime,
                    'end_time' => $maxScheduleTime
                ];
            } else {
                $unavailableTime['unavailable_time'][] = [
                    'start_time' => $minScheduleTime,
                    'end_time' => Carbon::now('Australia/Sydney')->format('H:i'),
                ];
            }
        }

        $existingAppointments = $this->serviceAppointmentService->getAppointmentsFromDate($formatDate);

        $scheduleIntervals = $this->getScheduleIntervals($allSchedules);
        $noScheduleIntervals = $this->findNoScheduleIntervals($minScheduleTime, $maxScheduleTime, $scheduleIntervals);

        foreach ($noScheduleIntervals as $noScheduleInterval) {
            $unavailableTime['unavailable_time'][] = $noScheduleInterval;
        }

        $busyIntervals = $this->findBusyIntervals($existingAppointments, $allSchedules);
        foreach ($busyIntervals as $busyInterval) {
            $unavailableTime['unavailable_time'][] = $busyInterval;
        }

        return $unavailableTime;
    }

    /**
     * Get unavailable time for a specific date and staff
     *
     * @param string $date
     * @param int $staffId
     * @return array
     */
    public function getUnavailableTimeFromStaff($date, $staffId)
    {
        $allStaffSchedules = $this->staffService->getStaffScheduleAppointment(
            $staffId,
            $date
        );
        if ($allStaffSchedules->isEmpty()) {
            return $this->noScheduleTimeResponse();
        }

        $formatDate = Carbon::createFromFormat('Y-m-d', $date);
        $allSchedules = $allStaffSchedules->pluck('schedules')->flatten();
        $minScheduleTime = $allSchedules->min('start_time');
        $maxScheduleTime = $allSchedules->max('end_time');
        $unavailableTime = $this->initializeUnavailableTime($minScheduleTime, $maxScheduleTime);

        if ($formatDate->isToday()) {
            $this->handleTodaySchedules($formatDate, $minScheduleTime, $maxScheduleTime, $unavailableTime);
        }

        $scheduleIntervals = $this->getScheduleIntervals($allSchedules);
        $noScheduleIntervals = $this->findNoScheduleIntervals($minScheduleTime, $maxScheduleTime, $scheduleIntervals);

        foreach ($noScheduleIntervals as $noScheduleInterval) {
            $unavailableTime['unavailable_time'][] = $noScheduleInterval;
        }

        $existingAppointments = $allStaffSchedules->pluck('bookingServices')->flatten();
        $busyIntervals = $this->findBusyIntervalsOfStaff($existingAppointments);

        foreach ($busyIntervals as $busyInterval) {
            $unavailableTime['unavailable_time'][] = $busyInterval;
        }

        return $unavailableTime;
    }
    private function findBusyIntervals(Collection $appointments, Collection $schedules)
    {
        $events = [];
        foreach ($appointments as $appointment) {
            $events[] = ['time' => Carbon::parse($appointment['booking_time']), 'type' => 'start'];
            $serviceDuration = $appointment['service_duration'] ?? 0;
            $expectedEndTime = Carbon::parse($appointment['booking_time'])->addMinutes($serviceDuration);
            $events[] = ['time' => $expectedEndTime, 'type' => 'end'];
        }

        usort($events, function ($a, $b) {
            if ($a['time']->eq($b['time'])) {
                return $a['type'] === 'start' ? -1 : 1;
            }
            return $a['time']->lt($b['time']) ? -1 : 1;
        });

        $busyPeriods = [];
        $currentCount = 0;
        $startTime = null;

        foreach ($events as $event) {
            $limit = $schedules
                ->where('start_time', '<=', $event['time']->toTimeString())
                ->where('end_time', '>=', $event['time']->toTimeString())
                ->count();

            if ($event['type'] === 'start') {
                $currentCount++;
            } else {
                $currentCount--;
            }

            if ($currentCount >= $limit && !$startTime) {
                $startTime = $event['time'];
            }

            if ($currentCount < $limit && $startTime) {
                $busyPeriods[] = [
                    'start_time' => $startTime->toTimeString(),
                    'end_time' => $event['time']->toTimeString(),
                ];
                $startTime = null;
            }
        }

        return $busyPeriods;
    }

    /**
     * Find intervals where there are no schedules
     *
     * @param string $beginTime
     * @param string $endTime
     * @param Collection $scheduleIntervals
     * @return array
     */
    private function findNoScheduleIntervals($beginTime, $endTime, Collection $scheduleIntervals)
    {
        $gaps = [];
        $cursor = Carbon::createFromFormat('H:i', $beginTime);
        foreach ($scheduleIntervals as $interval) {
            if ($interval['start']->gt($cursor)) {
                $gaps[] = [
                    'start_time' => $cursor->format('H:i'),
                    'end_time' => $interval['start']->format('H:i'),
                ];
            }
            if ($interval['end']->gt($cursor)) {
                $cursor = $interval['end']->copy();
            }
        }
        if ($cursor->lt($endTime)) {
            $gaps[] = [
                'start_time' => $cursor->format('H:i'),
                'end_time' => $endTime,
            ];
        }
        return $gaps;
    }

    private function getStaffScheduleAppointment($staff, $formatDate)
    {
        return Staff::select('id', 'status')
            ->where('id', $staff)
            ->whereHas('schedules', function ($query) use ($formatDate) {
                $query->where('status', 'active')
                    ->where('work_date', '=', $formatDate->format('Y-m-d'));
            })
            ->with('schedules', function ($query) use ($formatDate) {
                $query->select('id', 'staff_id', 'start_time', 'end_time', 'work_date', 'status')
                    ->where('status', 'active')
                    ->where('work_date', '=', $formatDate->format('Y-m-d'));
            })
            ->with('bookingServices', function ($query) use ($formatDate) {
                $query->select('id', 'staff_id', 'booking_time', 'service_duration')
                    ->where('booking_time', '>=', $formatDate->format('Y-m-d 00:00:00'))
                    ->where('booking_time', '<=', $formatDate->format('Y-m-d 23:59:59'));
            })
            ->where('status', 'active')
            ->get();
    }

    private function noScheduleTimeResponse()
    {
        return [
            'start_time' => "07:00",
            'end_time' => "20:00",
            'no_schedule' => true,
            'unavailable_time' => [
                [
                    'start_time' => "07:00",
                    'end_time' => "20:00",
                ]
            ],
        ];
    }

    private function initializeUnavailableTime($minScheduleTime, $maxScheduleTime)
    {
        return [
            'start_time' => $minScheduleTime,
            'end_time' => $maxScheduleTime,
            'unavailable_time' => [],
        ];
    }

    private function handleTodaySchedules($formatDate, $minScheduleTime, $maxScheduleTime, &$unavailableTime)
    {
        $todaySchedule = $this->getAvailableScheduleByDate($formatDate->format('Y-m-d'));
        if ($todaySchedule->isEmpty()) {
            $unavailableTime['unavailable_time'][] = [
                'start_time' => $minScheduleTime,
                'end_time' => $maxScheduleTime,
            ];
        } else {
            $now = Carbon::now('Australia/Sydney')->format('H:i');
            if ($now > $minScheduleTime) {
                $unavailableTime['unavailable_time'][] = [
                    'start_time' => $minScheduleTime,
                    'end_time' => $now
                ];
            }
        }
    }

    private function getScheduleIntervals($allSchedules)
    {
        return collect($allSchedules)->map(function ($item) {
            return [
                'start' => Carbon::createFromFormat('H:i', $item->start_time),
                'end' => Carbon::createFromFormat('H:i', $item->end_time),
            ];
        })->sortBy('start')->values();
    }

    private function findBusyIntervalsOfStaff(Collection $appointments, $limit = 1)
    {
        $events = [];
        foreach ($appointments as $appointment) {
            $events[] = ['time' => Carbon::parse($appointment['booking_time']), 'type' => 'start'];
            $serviceDuration = $appointment['service_duration'] ?? 0;
            $expectedEndTime = Carbon::parse($appointment['booking_time'])->addMinutes($serviceDuration);
            $events[] = ['time' => $expectedEndTime, 'type' => 'end'];
        }

        usort($events, function ($a, $b) {
            if ($a['time']->eq($b['time'])) {
                return $a['type'] === 'start' ? -1 : 1;
            }
            return $a['time']->lt($b['time']) ? -1 : 1;
        });

        $busyPeriods = [];
        $currentCount = 0;
        $startTime = null;

        foreach ($events as $event) {
            if ($event['type'] === 'start') {
                $currentCount++;
            } else {
                $currentCount--;
            }

            if ($currentCount >= $limit && !$startTime) {
                $startTime = $event['time'];
            }

            if ($currentCount < $limit && $startTime) {
                $busyPeriods[] = [
                    'start_time' => $startTime->toTimeString(),
                    'end_time' => $event['time']->toTimeString(),
                ];
                $startTime = null;
            }
        }

        return $busyPeriods;
    }


    /**
     * Get all available schedules grouped by date
     *
     * @return Collection
     */
    public function getAvailableSchedules()
    {
        $availableSchedules = [];

        // Group schedules by Date
        $groupedSchedules = $this->getAllAvailableSchedules()->groupBy(function ($schedule) {
            return Carbon::parse($schedule->work_date)->format('Y-m-d');
        });
        // Loop through each date and add appointment states
        foreach ($groupedSchedules as $date => $schedules) {
            $availableSchedules[$date] = [
                'schedules_id' => $schedules->pluck('id')->toArray(),
                'date' => Carbon::parse($date)->format('Y/m/d'),
            ];
        }
        return collect($availableSchedules)->values();
    }


    public function getAvailableSchedulesAndStatus()
    {
        $availableSchedules = [];
        $staffNumbers = 3;

        // Group schedules by Date
        $groupedSchedules = $this->getAllAvailableSchedules()->groupBy(function ($schedule) {
            return Carbon::parse($schedule->work_date)->format('Y-m-d');
        });

        // Get existing appointments
        $existingAppointments = app(AppointmentService::class)->getAllAppointments()->groupBy(function ($schedule) {
            return Carbon::parse($schedule->booking_time)->format('Y-m-d');
        })->map(function ($appointments) {
            return $appointments->count();
        })->toArray();

        // Loop through each date and add appointment states
        foreach ($groupedSchedules as $date => $schedules) {
            $bookingState = 'free';
            if (isset($existingAppointments[$date])) {
                $bookingState = $staffNumbers - $existingAppointments[$date] <= 1 ? 'nearFull' : 'hasBooking';
            }
            $availableSchedules[$date] = [
                'schedules_id' => $schedules->pluck('id')->toArray(),
                'date' => Carbon::parse($date)->format('Y/m/d'),
                'status' => $bookingState,
            ];
        }
        return collect($availableSchedules)->values();
    }

    public function getStaffScheduleStatistics()
    {
        $staffSchedules = $this->scheduleRepository->getStaffScheduleStatistics();
        $staffStatistics = [];

        foreach ($staffSchedules as $schedule) {
            $staffId = $schedule->staff_id;
            if (!isset($staffStatistics[$staffId])) {
                $staffStatistics[$staffId] = [
                    'total_schedules' => 0,
                    'available_schedules' => 0,
                    'unavailable_schedules' => 0,
                ];
            }
            $staffStatistics[$staffId]['total_schedules']++;
            if ($schedule->status === 'active') {
                $staffStatistics[$staffId]['available_schedules']++;
            } else {
                $staffStatistics[$staffId]['unavailable_schedules']++;
            }
        }

        return collect($staffStatistics)->values();
    }
}
