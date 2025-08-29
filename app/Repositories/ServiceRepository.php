<?php

namespace App\Repositories;

use App\Contracts\ServiceContract;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceRepository implements ServiceContract
{
    public function index()
    {
        return Service::with('package')->get();
    }

    public function store(Request $request)
    {
        return Service::create($request->all());
    }

    public function show($id)
    {
        return Service::with('package')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->all());
        return $service;
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
        return null;
    }

    public function getServiceByPackage($packageId)
    {
        return Service::where('package_id', $packageId)
        ->where('is_viewable', true)

        ->get();
    }
}
