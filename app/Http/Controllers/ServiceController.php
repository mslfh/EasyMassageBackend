<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\Request;

class ServiceController extends BaseController
{
    protected $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Service::with('package')->get());
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
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'status' => 'required|string',
            'price' => 'required|numeric',
            'is_viewable' => 'required|boolean',
        ]);

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        return response()->json($service->load('package'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'package_id' => 'sometimes|exists:packages,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'status' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'is_viewable' => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(null, 204);
    }

    /**
     * Get services by package ID.
     */
    public function getServiceByPackage($id)
    {
        $services = $this->serviceService->getServiceByPackage($id);
        return response()->json($services);
    }
}
