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
            ['name' => 'BIAYA USAHA', 'type' => 'budget', 'balance_side' => 'debit'],
            ['name' => 'BIAYA LAIN-LAIN', 'type' => 'budget', 'balance_side' => 'debit'],
            ['name' => 'PENDAPATAN', 'type' => 'budget', 'balance_side' => 'credit'],
            ['name' => 'PAJAK', 'type' => 'budget', 'balance_side' => 'debit'],
            ['name' => 'HUTANG PAJAK', 'type' => 'budget', 'balance_side' => 'credit'],
            ['name' => 'MODAL', 'type' => 'budget', 'balance_side' => 'credit'],
        ];

        // Menggunakan updateOrCreate agar seeder bisa dijalankan
        // berulang kali tanpa membuat duplikat data.
        foreach ($groupings as $group) {
            AccountGrouping::updateOrCreate(
                [
                    'name' => $group['name'],
                    'type' => $group['type'],
                    'balance_side' => $group['balance_side'] ?? null,
                ],
                $group // Data untuk di-insert atau di-update
            );
        }
    }
}
