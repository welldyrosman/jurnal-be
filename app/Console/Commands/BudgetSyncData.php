<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Budget\JurnalBudgetFetcher;
use App\Services\Budget\JurnalBudgetImporter;
use Carbon\Carbon;
use Throwable;

class BudgetSyncData extends Command
{
    protected $signature = 'budget:sync-data';

    protected $description = 'Sync budget management data from Jurnal API (yearly from 2014 until next year)';

    public function handle(
        JurnalBudgetFetcher $fetcher,
        JurnalBudgetImporter $importer
    ): int {
        $this->info('🔄 Start syncing budget data from Jurnal...');

        try {
            // ==========================
            // CONFIG
            // ==========================
            $accessToken = config('services.jurnal.api_key');
            $templateId  = 16224;
            $interval    = 1;
            $noInterval  = 12;

            $startYear = 2014;
            $endYear   = now()->year + 1; // tahun depan

            // ==========================
            // LOOP PER TAHUN
            // ==========================
            for ($year = $startYear; $year <= $endYear; $year++) {
                $startPeriod = "01/01/{$year}";

                $this->line("📆 Sync year: {$year}");

                // FETCH
                $report = $fetcher->fetch(
                    accessToken: $accessToken,
                    templateId: $templateId,
                    startPeriod: $startPeriod,
                    interval: $interval,
                    noInterval: $noInterval
                );

                // IMPORT
                $importer->import(
                    report: $report,
                    templateId: $templateId
                );

                $this->info("✅ Year {$year} synced");
            }

            $this->info('🎉 All years synced successfully');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Budget sync failed');
            $this->error($e->getMessage());

            report($e);
            return Command::FAILURE;
        }
    }
}
