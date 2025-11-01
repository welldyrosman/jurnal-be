<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Jurnal\SyncInvoicesService;
use App\Services\Jurnal\SyncLogService;
use Throwable;

class SyncJurnalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jurnal:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi semua data yang diperlukan dari Jurnal.id';

    /**
     * Execute the console command.
     *
     * @param SyncLogService $logService
     * @param SyncInvoicesService $invoiceService
     * @return int
     */
    public function handle(SyncLogService $logService, SyncInvoicesService $invoiceService): int
    {
        $this->info("🚀 Memulai proses sinkronisasi Jurnal.id...");
        
        // --- Sinkronisasi Invoices ---
        $log = $logService->start('invoices');
        try {
            $totalSynced = $invoiceService->sync($this);
            $logService->finish($log, $totalSynced);
        } catch (Throwable $e) {
            $logService->fail($log, $e);
            $this->error("❌ Sinkronisasi Invoices GAGAL. Cek file log untuk detail.");
            return Command::FAILURE; // Hentikan jika gagal
        }

        // Anda bisa menambahkan modul lain di sini di masa depan
        // $logPersons = $logService->start('persons');
        // ...

        $this->info("✅ Semua proses sinkronisasi Jurnal.id telah selesai!");
        return Command::SUCCESS;
    }
}

