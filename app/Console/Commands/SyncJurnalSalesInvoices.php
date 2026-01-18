<?php

namespace App\Console\Commands;

use App\Services\Jurnal\SyncSalesInvoiceService;
use Illuminate\Console\Command;

class SyncJurnalSalesInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jurnal:sync-sales-invoices
                            {--start-date=1/1/2016 :  Tanggal awal sinkronisasi}
                            {--single-id= :  Sinkronisasi invoice tertentu berdasarkan ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi sales invoices dari Jurnal API';

    /**
     * Execute the console command.
     */
    public function handle(SyncSalesInvoiceService $syncService): int
    {
        $startDate = $this->option('start-date');
        $singleId = $this->option('single-id');

        $this->info('🔄 Starting Jurnal Sales Invoices Sync.. .');
        $this->line('');

        try {
            if ($singleId) {
                // Sinkronisasi single invoice
                $this->syncSingleInvoice($syncService, (int)$singleId);
            } else {
                // Sinkronisasi semua invoices
                $this->syncAllInvoices($syncService, $startDate);
            }

            $this->showStats($syncService);
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Sync failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Sinkronisasi semua invoices
     */
    private function syncAllInvoices(SyncSalesInvoiceService $syncService, string $startDate): void
    {
        $this->info("📅 Start Date: {$startDate}");
        $this->line('');

        $bar = $this->output->createProgressBar();
        $bar->start();

        $result = $syncService->syncAllSalesInvoices($startDate);

        $bar->finish();
        $this->line('');
        $this->line('');

        // Display results
        $this->showResults($result);
    }

    /**
     * Sinkronisasi single invoice
     */
    private function syncSingleInvoice(SyncSalesInvoiceService $syncService, int $invoiceId): void
    {
        $this->info("🔍 Syncing Invoice ID: {$invoiceId}");
        $this->line('');

        $success = $syncService->syncSingleInvoice($invoiceId);

        if ($success) {
            $this->info("✅ Successfully synced invoice ID: {$invoiceId}");
        } else {
            $this->error("❌ Failed to sync invoice ID: {$invoiceId}");
        }
    }

    /**
     * Tampilkan hasil sinkronisasi
     */
    private function showResults(array $result): void
    {
        $this->info('📊 Sync Results:');
        $this->line("  ✅ Success: {$result['success']}");
        $this->line("  ❌ Failed: {$result['failed']}");
        $this->line("  📈 Total: {$result['total']}");

        if (! empty($result['errors'])) {
            $this->line('');
            $this->warn('⚠️  Errors:');
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($result['errors']) > 10) {
                $this->line("  ... and " . (count($result['errors']) - 10) . " more errors");
            }
        }
    }

    /**
     * Tampilkan statistik
     */
    private function showStats(SyncSalesInvoiceService $syncService): void
    {
        $stats = $syncService->getSyncStats();

        $this->line('');
        $this->info('📈 Database Statistics:');
        $this->line("  Total Records: {$stats['total']}");
        $this->line("  Synced: {$stats['synced']}");
        $this->line("  Pending: {$stats['pending']}");
        $this->line("  Failed: {$stats['failed']}");

        if ($stats['last_sync']) {
            $this->line("  Last Sync: {$stats['last_sync']}");
        }
    }
}
