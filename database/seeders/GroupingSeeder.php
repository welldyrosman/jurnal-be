<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountGrouping; // Menggunakan model Grouping Anda
use Illuminate\Support\Facades\DB;

class GroupingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi data master untuk 'groupings'
     */
    public function run(): void
    {
        // Data master untuk grouping, berdasarkan screenshot Excel
        $groupings = [
            // --- Tipe 'akun' ---
            ['name' => 'PENDAPATAN USAHA', 'type' => 'akun'],
            ['name' => 'PENDAPATAN LAIN-LAIN', 'type' => 'akun'],
            ['name' => 'BEBAN OPERASIONAL', 'type' => 'akun'],
            ['name' => 'BEBAN LAIN-LAIN', 'type' => 'akun'],
            ['name' => 'BEBAN PAJAK', 'type' => 'akun'],
            
            // --- Tipe 'budget' ---
            ['name' => 'BIAYA USAHA', 'type' => 'budget'],
            ['name' => 'BIAYA LAIN-LAIN', 'type' => 'budget'],
            ['name' => 'PENDAPATAN', 'type' => 'budget'],
        ];

        // Menggunakan updateOrCreate agar seeder bisa dijalankan
        // berulang kali tanpa membuat duplikat data.
        foreach ($groupings as $group) {
            AccountGrouping::updateOrCreate(
                [
                    'name' => $group['name'], 
                    'type' => $group['type']
                ], 
                $group // Data untuk di-insert atau di-update
            );
        }
    }
}