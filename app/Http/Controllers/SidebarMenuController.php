<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarMenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->role_id) {
            return $this->successResponse([
                'menu_groups' => [
                    [
                        'title' => 'Menu',
                        'items' => [],
                    ],
                ],
            ], 'Sidebar menu retrieved successfully');
        }

        $menus = Menu::query()
            ->orderByRaw('COALESCE(parent_id, 0), id')
            ->get();

        $allowedMenuIds = AccessControl::query()
            ->where('role_id', $user->role_id)
            ->pluck('menu_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $rootMenus = $menus->where('type', 'menu')->whereNull('parent_id')->values();

        $items = $rootMenus->map(function ($root) use ($menus, $allowedMenuIds) {
            $featureMenus = $menus
                ->where('type', 'menu')
                ->where('parent_id', $root->id)
                ->values()
                ->filter(function ($menu) use ($menus, $allowedMenuIds) {
                    if (in_array((int) $menu->id, $allowedMenuIds, true)) {
                        return true;
                    }

                    $contentIds = $menus
                        ->where('type', 'content')
                        ->where('parent_id', $menu->id)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();

                    if (empty($contentIds)) {
                        return false;
                    }

                    foreach ($contentIds as $contentId) {
                        if (in_array($contentId, $allowedMenuIds, true)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->map(fn($menu) => [
                    'name' => $menu->name,
                    'path' => $menu->url,
                ])
                ->filter(fn($subItem) => !empty($subItem['path']))
                ->values()
                ->all();

            return [
                'icon' => $root->icon,
                'name' => $root->name,
                'subItems' => $featureMenus,
            ];
        })->filter(fn($item) => !empty($item['subItems']))->values()->all();

        return $this->successResponse([
            'menu_groups' => [
                [
                    'title' => 'Menu',
                    'items' => $items,
                ],
            ],
        ], 'Sidebar menu retrieved successfully');
    }
}
