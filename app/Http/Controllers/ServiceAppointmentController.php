<?php

namespace App\Http\Controllers;

use App\Models\ServiceAppointment;
use App\Services\ServiceAppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceAppointmentController extends BaseController
{
    protected $serviceAppointmentService;

    public function __construct(ServiceAppointmentService $serviceAppointmentService)
    {
        $this->serviceAppointmentService = $serviceAppointmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json($this->serviceAppointmentService->getAllServiceAppointments());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'service_id' => 'required|exists:services,id',
            'staff_id' => 'nullable|exists:staff,id',
            'booking_time' => 'required|date',
            'expected_end_time' => 'required|date',
            'comments' => 'nullable|string',
        ]);
        return response()->json($this->serviceAppointmentService->createServiceAppointment($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json($this->serviceAppointmentService->getServiceAppointmentById($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceAppointment $serviceAppointment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        if(isset($data['date']) && isset($data['time'])) {
            $booking_time = Carbon::createFromFormat('Y-m-d H:i', $data['date'] . ' ' . $data['time']);
            $data['booking_time'] = $booking_time;
            unset($data['date']);
            unset($data['time']);
        }
        if (isset($data['service']['id'])) {
            $service = Service::with('package')->findOrFail($data['service']['id']);
            $data['package_id'] = $service->package_id;
            $data['package_title'] = $service->package->title;
            $data['package_hint'] = $service->package->hint;
            $data['service_id'] = $service->id;
            $data['service_title'] = $service->title;
            $data['service_description'] = $service->description;
            $data['service_duration'] = $service->duration;
            $data['service_price'] = $service->price;
            unset($data['service']);
        }
        if (isset($data['staff']['id'])) {
            $data['staff_id'] = $data['staff']['id'];
            $data['staff_name'] = $data['staff']['name'];
            unset($data['staff']);
        }
        return response()->json($this->serviceAppointmentService->updateServiceAppointment($id, $data));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->serviceAppointmentService->deleteServiceAppointment($id);
        return response()->json(null, 204);
    }
}
