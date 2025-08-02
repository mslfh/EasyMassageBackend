<?php

namespace App\Repositories;

use App\Models\User;
use App\Contracts\UserContract;

class UserRepository implements UserContract
{
    public function getAll()
    {
        // Get all users with a limit of 200
        return User::where('id', '!=', 1)
            ->where('id', '!=', 2)
            ->whereDoesntHave('staff')
            ->limit(200)->get();
    }

    public function getPaginatedUsers($start, $count, $filter, $sortBy, $descending)
    {
        $query = User::query();
        $notInIds = [0, 1, 2];
        if(auth()->user()['id'] == 0){
             $notInIds = [];
        }
        $query->whereNotIn('id', $notInIds)
            ->whereDoesntHave('staff')
            ->when($filter, function ($query) use ($filter) {
                return $query->where(function ($subQuery) use ($filter) {
                    $subQuery->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('email', 'like', '%' . $filter . '%');
                });
            })
            ->with('userProfile')
            ->orderBy($sortBy, $descending ? 'desc' : 'asc');
        $total = clone($query);
        $total = $total->count();
        $data = $query->skip($start)->take($count)->get();
        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public function fetchByKeyword($field)
    {
        return User::where('name', 'like', '%' . $field . '%')
            ->orWhere('phone', 'like', '%' . $field . '%')
            ->orWhere('email', 'like', '%' . $field . '%')
            ->limit(20)
            ->get();
    }

    public function findByField($search, $field = 'phone', $fuzzy=true )
    {
        if($fuzzy){
            return User::where($field, 'like', '%' . $search . '%')
                ->where('id', '!=', 1)
                ->where('id', '!=', 2)
                ->whereDoesntHave('staff')
                ->get();
        }
        return User::where($field, $search)
            ->where('id', '!=', 1)
            ->where('id', '!=', 2)
            ->whereDoesntHave('staff')
            ->get();
    }

    public function findById($id)
    {
        return User::findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        $user = $this->findById($id);
        return $user->delete();
    }

    public function verifyCurrentPassword($currentPassword)
    {
        $user = auth()->user();
        return password_verify($currentPassword, $user->password);
    }
}
