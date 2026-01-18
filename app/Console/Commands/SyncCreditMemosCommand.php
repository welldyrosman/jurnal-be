<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Jurnal\SyncCreditMemoService;

class SyncCreditMemosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jurnal:sync-credit-memos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data Credit Memo (Nota Kredit) dari Jurnal.id';

    /**
     * Execute the console command.
     */
    public function handle(SyncCreditMemoService $service)
    {
        $this->info('🚀  Memulai proses sinkronisasi Credit Memos...');
        $startTime = microtime(true);

        // Menjalankan service
        // Service akan mengembalikan array statistik ['success', 'failed', 'deleted', 'errors']
        $results = $service->sync();

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();

        // Tampilkan Statistik dalam Tabel
        $this->table(
            ['Metric', 'Count', 'Status'],
            [
                ['Total Data API', $results['total'] ?? 0, 'Info'],
                ['Berhasil Disimpan', $results['success'], '✅ OK'],
                ['Gagal', $results['failed'], $results['failed'] > 0 ? '❌ Check Logs' : '✅ OK'],
                ['Dihapus (Pruned)', $results['deleted'], '🧹 Cleaned'],
            ]
        );

        $this->newLine();

        // Tampilkan Error jika ada
        if (!empty($results['errors'])) {
            $this->error('⚠️  Ditemukan beberapa error selama sinkronisasi:');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->error(" - " . $error);
            }
            if (count($results['errors']) > 10) {
                $this->comment(" ... dan " . (count($results['errors']) - 10) . " error lainnya (lihat Log).");
            }
            $this->newLine();
        }

        $this->info("🏁  Selesai dalam {$duration} detik.");

        return 0;
    }
}
