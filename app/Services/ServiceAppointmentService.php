<?php

namespace App\Services;

use App\Contracts\ServiceAppointmentContract;
use App\Services\AppointmentLogService;
class ServiceAppointmentService
{
    protected $repository;

    public function __construct(ServiceAppointmentContract $repository)
    {
        $this->repository = $repository;
    }

    public function createServiceAppointment(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateServiceAppointment(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteServiceAppointment(int $id)
    {
        return $this->repository->delete($id);
    }

    public function getServiceAppointmentById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function getAllServiceAppointments()
    {
        return $this->repository->getAll();
    }

    public function getAppointmentsFromDate($date)
    {
        return $this->repository->getAppointmentsFromDate($date);
    }

    public function getServiceStatistics($beginDate, $endDate = null)
    {
        $serviceAppointments = $this->getAppointmentsFromDate($endDate);
        //fetch top_services,top_services_count,top_staff,top_staff_appointments
        $statistics = [
            'top_services' => "",
            'top_services_count' => 0,
            'top_staff' => "",
            'top_staff_appointments' => 0,
        ];
        $serviceCount = [];
        $staffCount = [];
        foreach ($serviceAppointments as $serviceAppointment) {
            if($serviceAppointment->service_title === "Break") {
                continue; // Skip break appointments
            }

            $serviceTitle = $serviceAppointment->service_title;
            if (isset($serviceCount[$serviceTitle])) {
                $serviceCount[$serviceTitle]++;
            } else {
                $serviceCount[$serviceTitle] = 1;
            }

            $staffName = $serviceAppointment->staff_name;
            if (isset($staffCount[$staffName])) {
                $staffCount[$staffName]++;
            } else {
                $staffCount[$staffName] = 1;
            }
        }
        if (!empty($serviceCount)) {
            arsort($serviceCount);
            $topService = array_key_first($serviceCount);
            $statistics['top_services'] = $topService;
            $statistics['top_services_count'] = $serviceCount[$topService];
        }
        if (!empty($staffCount)) {
            arsort($staffCount);
            $topStaff = array_key_first($staffCount);
            $statistics['top_staff'] = $topStaff;
            $statistics['top_staff_appointments'] = $staffCount[$topStaff];
        }
        return $statistics;
    }
}
