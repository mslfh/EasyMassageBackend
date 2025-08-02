<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

class UserController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $start = $request->query('start', 0);
        $count = $request->query('count', 10);
        $filter = $request->query('filter', null);
        $sortBy = $request->query('sortBy', 'id');
        $descending = $request->query('descending', false);
        $users = $this->userService->getPaginatedUsers($start, $count, $filter, $sortBy, $descending);
        return response()->json([
            'rows' => $users['data'],
            'total' => $users['total'],
        ]);

    }

    public function getByKeyword(Request $request)
    {
        $data = $request->validate([
            'search' => 'required|string|max:255',
        ]);
        return response()->json($this->userService->fetchByKey($data));
    }

    public function findByField(Request $request)
    {
        $data = $request->validate([
            'search' => 'required|string|max:255',
            'field' => 'required|string|max:255',
        ]);
        return response()->json($this->userService->findByField($data));
    }

    public function show($id)
    {
        return response()->json($this->userService->getUserById($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'phone' => 'required|string|max:15|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
        ]);
        return response()->json($this->userService->createUser($data), 201);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:2048',
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));
            return response()->json(['message' => 'Users imported successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to import users: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'sometimes|string|min:8',
            'phone' => 'required|string|max:15',
        ]);
        return response()->json($this->userService->updateUser($id, $data));
    }

    public function destroy($id)
    {
        $this->userService->deleteUser($id);
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|',
        ]);
        return response()->json($this->userService->changePassword($data));
    }

}

