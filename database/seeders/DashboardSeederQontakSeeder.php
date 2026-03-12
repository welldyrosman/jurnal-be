<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardSeederQontakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $dashboard = Menu::where('name', 'Dashboard')->first();
        if (!$dashboard) {
            return;
        }

        $menu = Menu::updateOrCreate([
            'name' => 'Dashboard Qontak 2',
            'type' => 'menu',
            'parent_id' => $dashboard->id,
        ], [
            'url' => '/home-qontak-2',
            'icon' => null,
            'updated_at' => $now,
        ]);

        $contents = [
            'SALES_PERFORMANCE',
            'YEARLY_SALES_COMPARISON',
            'DEALS_WON',
            'SUMMARY_REPORT',
            'DEALS_BY_STAGE',
            'SOURCES',
            'LOST_REASONS',
            'TASKS',
            'WEIGHTED_AVERAGE_DEALS_BY_STAGE',
            'DEALS_PIPELINE_CONVERSION',
            'CUMULATIVE_DAILY_SALES_PERFORMANCE_BY_MONTH',
        ];

        DB::table('menus')
            ->where('type', 'content')
            ->where('parent_id', $menu->id)
            ->whereNotIn('name', $contents)
            ->delete();

        foreach ($contents as $name) {
            DB::table('menus')->updateOrInsert(
                [
                    'name' => $name,
                    'type' => 'content',
                    'parent_id' => $menu->id,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
