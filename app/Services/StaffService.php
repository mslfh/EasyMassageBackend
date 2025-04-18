<?php

namespace App\Services;

use App\Contracts\StaffContract;

class StaffService
{
    protected $staffRepository;

    public function __construct(StaffContract $staffRepository)
    {
        $this->staffRepository = $staffRepository;
    }


    public function getAvailableStaffFromScheduletime($dateTime,$duration)
    {
        return $this->staffRepository->getAvailableStaffFromScheduletime($dateTime,$duration);
    }

    public function getStaffScheduleFromDate($date)
    {
        return $this->staffRepository->getStaffScheduleFromDate($date);
    }
    public function getAvailableStaffFromScheduledate($dateTime)
    {
        return $this->staffRepository->getAvailableStaffFromScheduledate($dateTime);
    }

    public function getAllStaff()
    {
        return $this->staffRepository->getAll();
    }

    public function getStaffById($id)
    {
        return $this->staffRepository->getById($id);
    }

    public function createStaff(array $data)
    {
        return $this->staffRepository->create($data);
    }

    public function updateStaff($id, array $data)
    {
        return $this->staffRepository->update($id, $data);
    }

    public function deleteStaff($id)
    {
        return $this->staffRepository->delete($id);
    }
}
