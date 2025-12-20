<?php

namespace App\Console\Commands;

use App\Services\Jurnal\SyncPaymentService;
use Illuminate\Console\Command;

class SyncJurnalReceivePaymentsCommand extends Command
{
    protected $signature = 'jurnal:sync-payments {--failed :  Sync hanya data yang gagal}';
    protected $description = 'Sinkronisasi Receive Payments dari Jurnal API ke database lokal';

    public function __construct(
        private SyncPaymentService $SyncPaymentService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('🔄 Mulai sinkronisasi Receive Payments dari Jurnal API.. .');

        if ($this->option('failed')) {
            $results = $this->SyncPaymentService->syncFailedPayments();
        } else {
            $results = $this->SyncPaymentService->syncAllJurnalReceivePayments();
        }

        $this->info("\n✅ Sinkronisasi selesai!");
        $this->info("📊 Total:  {$results['total']}");
        $this->info("✓ Berhasil: {$results['success']}");
        $this->info("✗ Gagal: {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->error("\n⚠️  Errors:");
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        return Command::SUCCESS;
    }
}
