<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // /DB::table('roles')->truncate();

        $now = Carbon::now();

        DB::table('roles')->insert([
            [
                'name' => 'Super Admin',
                'description' => 'Memiliki akses penuh ke seluruh sistem',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Admin',
                'description' => 'Mengelola data dan operasional sistem',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Manager',
                'description' => 'Monitoring laporan dan approval',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Staff',
                'description' => 'User operasional harian',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Guest',
                'description' => 'Akses terbatas hanya untuk melihat data tertentu',
                'status' => 'inactive',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);
    }
}
