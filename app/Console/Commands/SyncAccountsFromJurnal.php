<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Jurnal\SyncAccountsService; // 1. Import service
use Throwable; // 2. Import untuk error handling

class SyncAccountsFromJurnal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jurnal:sync-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data Chart of Accounts (COA) dari Jurnal.id API (my.jurnal.id)';

    /**
     * Execute the console command.
     */
    public function handle(SyncAccountsService $syncService) // 3. Injeksi service
    {
        $this->info('🚀 Memulai sinkronisasi Chart of Accounts (COA)...');

        try {
            // 4. Panggil method sync() dari service
            // Service ini (SyncAccountsService.php) sudah memiliki
            // Log::info dan Log::error di dalamnya.
            $totalSynced = $syncService->sync();

            $this->info("✅ Sinkronisasi COA selesai. Total akun (root level) diproses: {$totalSynced}");
            return self::SUCCESS;
        } catch (Throwable $e) {
            // 5. Tangkap error jika API gagal total (misal: token salah)
            $this->error('❌ Sinkronisasi COA Gagal Total.');
            $this->error('Pesan: ' . $e->getMessage());
            $this->error('Cek file log (storage/logs/laravel.log) untuk detail trace.');
            return self::FAILURE;
        }
    }
}
