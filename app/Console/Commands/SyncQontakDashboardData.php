<?php

namespace App\Console\Commands;

use App\Jobs\SyncQontakDashboardDataJob;
use Illuminate\Console\Command;
use Throwable;

class SyncQontakDashboardData extends Command
{
    protected $signature = 'qontak:sync-dashboard-data
        {--queue : Dispatch job ke queue database}
        {--no-timeline : Skip sinkronisasi timeline entity}
        {--progress : Tampilkan progress detail (mode non-queue)}
        {--limit= : Batasi jumlah entity (deal/contact/company) untuk stage history & timeline}';

    protected $description = 'Sinkronisasi data Qontak untuk kebutuhan Dashboard Qontak 2 (11 content)';

    public function handle(): int
    {
        $syncTimelines = !$this->option('no-timeline');
        $showProgress = !$this->option('queue');

        $limit = $this->option('limit');
        $entityLimit = null;
        if (is_numeric($limit) && (int) $limit > 0) {
            $entityLimit = (int) $limit;
        }

        try {
            if ($this->option('queue')) {
                SyncQontakDashboardDataJob::dispatch($syncTimelines, $entityLimit);

                $this->info('✅ Job sync dashboard Qontak sudah masuk queue.');
                return self::SUCCESS;
            }

            $this->info('🚀 Menjalankan sync dashboard Qontak secara langsung...');
            $this->line('   - timeline: ' . ($syncTimelines ? 'ON' : 'OFF'));
            $this->line('   - limit entity: ' . ($entityLimit ? (string) $entityLimit : 'ALL'));
            $this->line('   - progress detail: ' . ($showProgress ? 'ON' : 'OFF'));
            $this->newLine();

            SyncQontakDashboardDataJob::dispatchSync($syncTimelines, $entityLimit, $showProgress);

            $this->info('✅ Sync dashboard Qontak selesai.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Sync dashboard Qontak gagal: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}
