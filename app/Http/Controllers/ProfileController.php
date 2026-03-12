<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('roleRelation:id,name,code,status');

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_id' => $user->role_id,
            'role_name' => $user->roleRelation?->name,
            'role_code' => $user->roleRelation?->code,
            'status' => $user->roleRelation?->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], 'Profil user berhasil diambil');
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_id' => $user->role_id,
        ], 'Profil berhasil diperbarui');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse('Password saat ini tidak sesuai.', 422);
        }

        if (Hash::check($validated['new_password'], $user->password)) {
            return $this->errorResponse('Password baru tidak boleh sama dengan password saat ini.', 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return $this->successResponse([], 'Password berhasil diperbarui');
    }
}

