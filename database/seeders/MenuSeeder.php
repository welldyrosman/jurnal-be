<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        //DB::table('menus')->truncate();

        $now = Carbon::now();

        // Dashboard
        $dashboardId = DB::table('menus')->insertGetId([
            'name' => 'Dashboard',
            'type' => 'menu',
            'icon' => 'GridIcon',
            'url' => null,
            'parent_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $jurnalId = DB::table('menus')->insertGetId([
            'name' => 'Dashboard Jurnal',
            'type' => 'menu',
            'url' => '/home',
            'icon' => null,
            'parent_id' => $dashboardId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('menus')->insert([
            [
                'name' => 'SALES_YTD',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'SALDO_BANK',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'INVOICE_STATUS_ALL',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'INVOICE_STATUS_YTD',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'BUDGET_VS_ACTUAL',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PIUTANG_BELUM_BAYAR',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PIUTANG_TELAH_BAYAR',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PIUTANG_BAYAR_LESS_THAN_30_DAYS',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'AGING_PIUTANG',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TOP_5_PIUTANG',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,

            ],
            [
                'name' => 'CASH_IN_OUT',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'BUDGET_VS_REALISASI',
                'type' => 'content',
                'parent_id' => $jurnalId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
        $qontakId = DB::table('menus')->insertGetId([
            'name' => 'Dashboard Qontak',
            'type' => 'menu',
            'url' => '/home-qontak',
            'icon' => null,
            'parent_id' => $dashboardId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('menus')->insert([
            [
                'name' => 'WIN_RATE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'AVG_DEAL_VAL',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'OPEN_PIPELINE_VALUE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TOTAL_REVENUE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PUBLIC_TRAINING',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,

            ],
            [
                'name' => 'KONSULTASI',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'INHOUSE_TRAINING',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,

            ],
            [
                'name' => 'PIPELINE_BY_STAGE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,

            ],
            [
                'name' => 'LEAD_SOURCE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,

            ],
            [
                'name' => 'TOP_5_PRODUCT',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TOP_5_LEAD_EMPLOYEE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,

            ],
            [
                'name' => 'TOP_5_WON_EMPLOYEE',
                'type' => 'content',
                'parent_id' => $qontakId,
                'created_at' => $now,
                'updated_at' => $now,

            ]
        ]);

        // Master Data
        $masterId = DB::table('menus')->insertGetId([
            'name' => 'Master Data',
            'type' => 'menu',
            'icon' => 'TableIcon',
            'url' => null,
            'parent_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $userManagementId = DB::table('menus')->insertGetId([
            'name' => 'User',
            'type' => 'menu',
            'url' => '/user-management',
            'icon' => null,
            'parent_id' => $masterId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('menus')->insert([
            [
                'name' => 'CREATE_USER',
                'type' => 'content',
                'parent_id' => $userManagementId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'EDIT_USER',
                'type' => 'content',
                'parent_id' => $userManagementId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'DELETE_USER',
                'type' => 'content',
                'parent_id' => $userManagementId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'CHANGE_PASSWORD',
                'type' => 'content',
                'parent_id' => $userManagementId,
                'created_at' => $now,
                'updated_at' => $now,

            ]
        ]);
        $controlAccessId = DB::table('menus')->insertGetId([
            'name' => 'Role & Permission',
            'type' => 'menu',
            'url' => '/control-access',
            'icon' => null,
            'parent_id' => $masterId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('menus')->insert([
            [
                'name' => 'CREATE_ROLE',
                'type' => 'content',
                'parent_id' => $controlAccessId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'EDIT_ROLE',
                'type' => 'content',
                'parent_id' => $controlAccessId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'DELETE_ROLE',
                'type' => 'content',
                'parent_id' => $controlAccessId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'MANAGE_PERMISSIONS',
                'type' => 'content',
                'parent_id' => $controlAccessId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        // Reporting
        $reportId = DB::table('menus')->insertGetId([
            'name' => 'Reporting',
            'type' => 'menu',
            'icon' => 'PieChartIcon',
            'url' => null,
            'parent_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('menus')->insert([
            [
                'name' => 'Laba Rugi',
                'type' => 'menu',
                'url' => '/profit-loss',
                'icon' => null,
                'parent_id' => $reportId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Project RO',
                'type' => 'menu',
                'url' => '/project-ro',
                'icon' => null,
                'parent_id' => $reportId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Pipeline Lead',
                'type' => 'menu',
                'url' => '/pipeline-lead',
                'icon' => null,
                'parent_id' => $reportId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
