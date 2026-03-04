<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $menus = Menu::query()
            ->orderByRaw('COALESCE(parent_id, 0), id')
            ->get();

        $roles = Role::query()
            ->with([
                'users:id,name,email,role_id',
                'accessControls:id,role_id,menu_id',
            ])
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->select('id', 'name', 'email', 'role_id')
            ->orderBy('name')
            ->get();

        return $this->successResponse([
            'menus' => $this->formatMenus($menus),
            'roles' => $roles->map(fn(Role $role) => $this->formatRole($role, $menus))->values(),
            'users' => $users,
        ], 'Role & access data retrieved successfully');
    }

    public function storeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'code' => ['nullable', 'string', 'max:255', 'unique:roles,code'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? Str::slug($validated['name'], '_'),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return $this->successResponse($role, 'Role created successfully', 201);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'code' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('roles', 'code')->ignore($role->id)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        if (array_key_exists('name', $validated)) {
            $role->name = $validated['name'];
        }

        if (array_key_exists('code', $validated)) {
            $role->code = $validated['code'] ?: Str::slug($role->name, '_');
        }

        if (array_key_exists('description', $validated)) {
            $role->description = $validated['description'];
        }

        if (array_key_exists('status', $validated)) {
            $role->status = $validated['status'];
        }

        $role->save();

        return $this->successResponse($role, 'Role updated successfully');
    }

    public function destroyRole(Role $role): JsonResponse
    {
        if (Role::count() <= 1) {
            return $this->errorResponse('Minimal harus ada satu role aktif di sistem.', 422);
        }

        $role->delete();

        return $this->successResponse([], 'Role deleted successfully');
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'access' => ['required', 'array'],
            'access.menu' => ['nullable', 'array'],
            'access.content' => ['nullable', 'array'],
        ]);

        $menuMap = Menu::query()->get()->keyBy(fn(Menu $menu) => $menu->code ?: ('menu_' . $menu->id));

        $selectedMenuIds = [];

        $menuAccess = $validated['access']['menu'] ?? [];
        foreach ($menuAccess as $menuKey => $enabled) {
            if (!$enabled) {
                continue;
            }

            $menu = $menuMap->get($menuKey);
            if ($menu) {
                $selectedMenuIds[] = $menu->id;
            }
        }

        $contentAccess = $validated['access']['content'] ?? [];
        foreach ($contentAccess as $contents) {
            if (!is_array($contents)) {
                continue;
            }

            foreach ($contents as $contentKey => $enabled) {
                if (!$enabled) {
                    continue;
                }

                $content = $menuMap->get($contentKey);
                if ($content) {
                    $selectedMenuIds[] = $content->id;
                }
            }
        }

        $selectedMenuIds = collect($selectedMenuIds)->unique()->values();
        $now = now();
        $hasPermissionColumn = Schema::hasColumn('access_controls', 'permission');

        DB::transaction(function () use ($role, $selectedMenuIds, $now, $hasPermissionColumn): void {
            AccessControl::query()->where('role_id', $role->id)->delete();

            if ($selectedMenuIds->isEmpty()) {
                return;
            }

            $rows = $selectedMenuIds->map(function ($menuId) use ($role, $now, $hasPermissionColumn) {
                $row = [
                    'role_id' => $role->id,
                    'menu_id' => $menuId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasPermissionColumn) {
                    $row['permission'] = 'view';
                }

                return $row;
            })->all();

            AccessControl::query()->insert($rows);
        });

        return $this->successResponse([], 'Access saved successfully');
    }

    public function assignUsers(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'mode' => ['nullable', Rule::in(['add', 'sync'])],
        ]);

        $userIds = collect($validated['user_ids'])->map(fn($id) => (int) $id)->unique()->values();
        $mode = $validated['mode'] ?? 'add';

        DB::transaction(function () use ($role, $userIds, $mode): void {
            if ($mode === 'sync') {
                User::query()
                    ->where('role_id', $role->id)
                    ->whereNotIn('id', $userIds)
                    ->update(['role_id' => null]);
            }

            User::query()->whereIn('id', $userIds)->update(['role_id' => $role->id]);
        });

        return $this->successResponse([], 'Users assigned to role successfully');
    }

    public function removeUser(Role $role, User $user): JsonResponse
    {
        if ((int) $user->role_id !== (int) $role->id) {
            return $this->errorResponse('User tidak berada pada role ini.', 422);
        }

        $user->role_id = null;
        $user->save();

        return $this->successResponse([], 'User removed from role successfully');
    }

    private function formatMenus($menus)
    {
        $rootMenus = $menus->where('type', 'menu')->whereNull('parent_id')->values();

        return $rootMenus
            ->flatMap(function (Menu $root) use ($menus) {
                $children = $menus
                    ->where('type', 'menu')
                    ->where('parent_id', $root->id)
                    ->values();

                return $children->map(function (Menu $child) use ($menus, $root) {
                    $contents = $menus
                        ->where('type', 'content')
                        ->where('parent_id', $child->id)
                        ->values()
                        ->map(fn(Menu $content) => [
                            'id' => $content->id,
                            'key' => $content->code ?: ('menu_' . $content->id),
                            'name' => $content->name,
                            'type' => $content->type,
                        ])
                        ->values();

                    return [
                        'id' => $child->id,
                        'key' => $child->code ?: ('menu_' . $child->id),
                        'name' => $child->name,
                        'type' => $child->type,
                        'group_name' => $root->name,
                        'group_key' => $root->code ?: ('menu_' . $root->id),
                        'url' => $child->url,
                        'icon' => $child->icon,
                        'contents' => $contents,
                    ];
                })->values();
            })
            ->values();
    }

    private function formatRole(Role $role, $menus): array
    {
        $access = [
            'menu' => [],
            'content' => [],
        ];

        $rootMenus = $menus->where('type', 'menu')->whereNull('parent_id')->pluck('id');
        $featureMenus = $menus
            ->where('type', 'menu')
            ->whereIn('parent_id', $rootMenus)
            ->values();

        foreach ($featureMenus as $menu) {
            $menuKey = $menu->code ?: ('menu_' . $menu->id);

            $access['menu'][$menuKey] = false;
            $access['content'][$menuKey] = [];

            $contents = $menus
                ->where('type', 'content')
                ->where('parent_id', $menu->id)
                ->values();

            foreach ($contents as $content) {
                $contentKey = $content->code ?: ('menu_' . $content->id);
                $access['content'][$menuKey][$contentKey] = false;
            }
        }

        $grantedIds = $role->accessControls->pluck('menu_id')->map(fn($id) => (int) $id)->all();

        foreach ($featureMenus as $menu) {
            $menuKey = $menu->code ?: ('menu_' . $menu->id);
            $access['menu'][$menuKey] = in_array((int) $menu->id, $grantedIds, true);

            $contents = $menus
                ->where('type', 'content')
                ->where('parent_id', $menu->id)
                ->values();

            foreach ($contents as $content) {
                $contentKey = $content->code ?: ('menu_' . $content->id);
                $access['content'][$menuKey][$contentKey] = in_array((int) $content->id, $grantedIds, true);
            }
        }

        return [
            'id' => $role->id,
            'name' => $role->name,
            'code' => $role->code ?: Str::slug($role->name, '_'),
            'description' => $role->description,
            'is_active' => $role->status === 'active',
            'status' => $role->status,
            'user_ids' => $role->users->pluck('id')->values(),
            'users' => $role->users,
            'access' => $access,
        ];
    }
}
