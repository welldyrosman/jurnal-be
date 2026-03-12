<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QontakDashboardOverrideSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('qontak_dashboard_overrides')->upsert([
            [
                'metric_code' => 'YEARLY_SALES_COMPARISON_WON_AMOUNT',
                'period_type' => 'year',
                'period_key' => '2024',
                'value' => 14280000,
                'note' => 'Backfill manual sesuai dokumen dashboard Qontak',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'metric_code' => 'YEARLY_SALES_COMPARISON_WON_AMOUNT',
                'period_type' => 'year',
                'period_key' => '2025',
                'value' => 19100000,
                'note' => 'Backfill manual sesuai dokumen dashboard Qontak',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], [
            'metric_code',
            'period_type',
            'period_key',
        ], [
            'value',
            'note',
            'active',
            'updated_at',
        ]);
    }
}
