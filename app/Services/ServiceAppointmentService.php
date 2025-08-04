<?php

namespace App\Services;

use App\Contracts\ServiceAppointmentContract;
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

    public function getAppointmentsFromDateRange($startDate, $endDate)
    {
        return $this->repository->getAppointmentsFromDateRange($startDate, $endDate);
    }

    public function getAnalyticsStatistics($beginDate, $endDate)
    {

        $serviceAppointments = $this->getAppointmentsFromDateRange($beginDate, $endDate);
        $statistics = [
            "service_statistics" => [
                'top_services' => "",
                'top_services_count' => 0,
                'top_staff' => "",
                'top_staff_appointments' => 0,
            ],
            "appointment_statistics" => [
                'total_appointments' => 0,
                'completed_appointments' => 0,
                'appointment_amount' => 0,
                'pending_appointments' => 0,
            ],
            "revenue_statistics" => [
                'total_revenue' => 0,
                'average_revenue_per_appointment' => 0,
                'paid_amount' => 0,
                'unpaid_amount' => 0,
            ],
        ];
        $serviceCount = [];
        $staffCount = [];
        $completedAppointments = 0;
        $pendingAppointments = 0;
        $appointment_amount = 0;
        $total_revenue = 0;
        $average_revenue_per_appointment = 0;
        $paid_amount = 0;
        $unpaid_amount = 0;

        foreach ($serviceAppointments as $serviceAppointment) {
            if ($serviceAppointment->service_title === "Break") {
                continue;
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
            // Check if appointment exists before accessing it
            if (!$serviceAppointment->appointment) {
                continue;
            }
            if ($serviceAppointment->appointment->status === 'finished') {
                $completedAppointments++;
            } elseif ($serviceAppointment->appointment->status === 'booked') {
                $pendingAppointments++;
            }
            $appointment_amount += $serviceAppointment->service_price;

            // Check if order exists before accessing it
            if (!$serviceAppointment->appointment->order) {
                continue;
            }
            $total_revenue += $serviceAppointment->appointment->order->total_amount;
            $paid_amount += $serviceAppointment->appointment->order->paid_amount;
            if ($serviceAppointment->appointment->order->payment_method === 'unpaid') {
                $unpaid_amount += $serviceAppointment->appointment->order->total_amount;
            }
        }
        if (!empty($serviceCount)) {
            arsort($serviceCount);
            $topService = array_key_first($serviceCount);
            $statistics['service_statistics']['top_services'] = $topService;
            $statistics['service_statistics']['top_services_count'] = $serviceCount[$topService];
        }
        if (!empty($staffCount)) {
            arsort($staffCount);
            $topStaff = array_key_first($staffCount);
            $statistics['service_statistics']['top_staff'] = $topStaff;
            $statistics['service_statistics']['top_staff_appointments'] = $staffCount[$topStaff];
        }
        $statistics['appointment_statistics']['total_appointments'] = count($serviceAppointments);
        $statistics['appointment_statistics']['completed_appointments'] = $completedAppointments;
        $statistics['appointment_statistics']['pending_appointments'] = $pendingAppointments;
        $statistics['appointment_statistics']['appointment_amount'] = $appointment_amount;

        if ($statistics['appointment_statistics']['total_appointments'] > 0) {
            //keep 2 decimal places of average_revenue_per_appointment
            $average_revenue_per_appointment = round($total_revenue / $statistics['appointment_statistics']['total_appointments'], 2);
        }
        $statistics['revenue_statistics']['total_revenue'] = $total_revenue;
        $statistics['revenue_statistics']['average_revenue_per_appointment'] = $average_revenue_per_appointment;
        $statistics['revenue_statistics']['paid_amount'] = $paid_amount;
        $statistics['revenue_statistics']['unpaid_amount'] = $unpaid_amount;
        return $statistics;
    }
}
