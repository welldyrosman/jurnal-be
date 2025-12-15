<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse; // Pastikan Anda mengimpor ini
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET: Menampilkan daftar semua user dalam format yang cocok untuk Easy Data Table.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('rowsPerPage', 10);
        $search = $request->input('search');
        $searchFields = $request->input('search_fields');
        $sortBy = $request->input('sortBy', 'created_at');
        $sortType = $request->input('sortType', 'desc');

        $query = User::select('id', 'name', 'email', 'role', 'created_at');

        if ($search && $searchFields) {
            $fields = explode(',', $searchFields);

            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere(trim($field), 'like', '%' . $search . '%');
                }
            });
        }

        $query->orderBy($sortBy, $sortType);

        $users = $query->paginate($perPage);

        return $this->easyDataTableResponse($users);
    }

    /**
     * POST: Membuat user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['nullable', Rule::in(['admin', 'user'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        return $this->successResponse(
            [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'User created successfully',
            201
        );
    }

    public function show(string $id)
    {
        $user = User::select('id', 'name', 'email', 'role', 'created_at')->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }
        return $this->successResponse($user, 'User retrieved successfully');
    }

    /**
     * PUT/PATCH: Memperbarui data user.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'user'])],
        ]);

        $data = $request->only('name', 'email', 'role');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return $this->successResponse($user, 'User updated successfully');
    }

    /**
     * DELETE: Menghapus user.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $user->delete();
        return $this->successResponse([], 'User deleted successfully', 204);
    }
}
