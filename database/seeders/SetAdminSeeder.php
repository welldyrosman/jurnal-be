<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SetAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@mi.com')->first();
        if (!$admin) {
            return;
        }

        // Ambil role admin
        $role = Role::where('name', 'Super Admin')->first() ?? Role::find(1);

        if (!$role) {
            return;
        }

        // Set role ke user admin
        $admin->update([
            'role_id' => $role->id
        ]);

        // Parent menu
        $menu = Menu::firstOrCreate(
            ['url' => '/control-access'],
            [
                'name' => 'Access Control',
                'icon' => 'ShieldCheck',
                'parent_id' => null,
            ]
        );

        // Child menu Roles
        $rolesMenu = Menu::firstOrCreate(
            ['url' => '/control-access/roles'],
            [
                'name' => 'Roles',
                'icon' => null,
                'parent_id' => $menu->id,
            ]
        );

        // Berikan akses ke role admin
        $menu->accessControls()->firstOrCreate([
            'role_id' => $role->id,
            'menu_id' => $menu->id,
        ]);

        $rolesMenu->accessControls()->firstOrCreate([
            'role_id' => $role->id,
            'menu_id' => $rolesMenu->id,
        ]);
    }
}
