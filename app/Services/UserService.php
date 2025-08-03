<?php

namespace App\Services;

use App\Contracts\UserContract;

class UserService
{
    protected $userRepository;

    public function __construct(UserContract $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers()
    {
        return $this->userRepository->getAll();
    }

    public function getUserById($id)
    {
        return $this->userRepository->findById($id);
    }

    public function fetchByKey($data)
    {
        $field = $data['search'];
        return $this->userRepository->fetchByKeyword($field);
    }

     public function findByField($data)
    {
        $search = $data['search'];
        $field = $data['field'] ?? 'phone';
        $fuzzy = $data['fuzzy'] ?? true;
        return $this->userRepository->findByField($search,$field,$fuzzy);
    }

    public function createUser(array $data)
    {
        $data['password'] = bcrypt($data['password']??$data['phone']);
        return $this->userRepository->create($data);
    }

    public function updateUser($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        return $this->userRepository->update($id, $data);
    }

    public function getPaginatedUsers($start, $count, $filter, $sortBy, $descending)
    {
        return $this->userRepository->getPaginatedUsers($start, $count, $filter, $sortBy, $descending);
    }

    public function deleteUser($id)
    {
        return $this->userRepository->delete($id);
    }

    public function changePassword(array $data)
    {
        // verify current password
        if (!$this->userRepository->verifyCurrentPassword($data['current_password'])) {
            throw new \Exception('Current password is incorrect');
        }
        $data['password'] = $data['new_password'];
        unset($data['current_password'], $data['new_password']);
        // update password
        $data['id'] = auth()->id(); // assuming the user is authenticated
        // update the password
        $this->updateUser($data['id'], $data);
        // return success response
        return ['message' => 'Password changed successfully'];

    }

    public function changeUserPassword($id, array $data)
    {
        // validate permission to change user password
        $adminId = [0,1,2];
        $userId = auth()->id();
        if (!in_array($userId, $adminId)) {
            throw new \Exception('Unauthorized action');
        }
        $data['password'] = $data['new_password'];
        unset($data['current_password'], $data['new_password']);
        // update the user's password
        return $this->updateUser($id, $data);
    }
}
