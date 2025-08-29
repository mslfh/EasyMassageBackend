<?php

namespace App\Repositories;

use App\Contracts\PackageContract;
use App\Models\Package;

class PackageRepository implements PackageContract
{
    public function getAll()
    {
        return Package::all();
    }

    public function getPackageWithService()
    {
        return Package::with([
            'services' => function ($query) {
                $query->where('status', 'active')
                    ->where('is_viewable', true);
            }
        ])->get();
    }

    public function findById($id)
    {
        return Package::findOrFail($id);
    }

    public function create(array $data)
    {
        return Package::create($data);
    }

    public function update($id, array $data)
    {
        $package = $this->findById($id);
        $package->update($data);
        return $package;
    }

    public function delete($id)
    {
        $package = $this->findById($id);
        $package->delete();
    }
}
