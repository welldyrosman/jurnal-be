<?php

namespace App\Services\Jurnal;

use App\Models\JurnalAccount;
use App\Models\JurnalAccountCat;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service ini menyinkronkan COA dari https://api.jurnal.id (Partner API)
 * Service ini MENG-EXTEND JurnalBaseService.
 */
class SyncAccountsService extends JurnalBaseService // <-- [FIX] Meng-extend Base Service
{
    // [FIX] __construct() dihapus.
    // Koneksi dan token otomatis diambil dari parent (JurnalBaseService).

    /**
     * Jalankan sinkronisasi COA.
     */
    public function sync(): int
    {
        Log::info('🚀 Memulai sinkronisasi Chart of Accounts (COA)...');
        $totalSynced = 0;

        try {
            // [FIX] Ambil respons penuh, lalu ekstrak key 'accounts'
            $response = $this->get('accounts');
            $accounts = $response['accounts'] ?? []; // <-- PERBAIKAN DI SINI

            if (empty($accounts)) {
                Log::info('✅ COA: Tidak ada data akun yang diterima.');
                return 0;
            }

            // Respons Jurnal API adalah array dari akun, kita proses satu per satu
            foreach ($accounts as $accountData) {
                // $accountData sekarang adalah akun individual, e.g., {"id": 101943288, ...}
                $this->syncAccountAndChildren($accountData, null);
                $totalSynced++;
            }

            Log::info("✅ Sinkronisasi COA selesai. Total akun (root level) diproses: {$totalSynced}");
            return $totalSynced;
        } catch (Throwable $e) {
            Log::error('❌ Gagal total sinkronisasi COA: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Menyimpan akun dan memproses 'children'-nya secara rekursif.
     * (Logika ini diasumsikan masih sama, berdasarkan respons JSON Anda sebelumnya)
     */
    private function syncAccountCat($cat_id, $cat_name)
    {
        JurnalAccountCat::updateOrCreate([
            "jurnal_cat_id" => $cat_id
        ], [
            "name" => $cat_name
        ]);
    }
    private function syncAccountAndChildren(array $accountData, ?int $parentId = null): void
    {
        // 1. Simpan/Perbarui akun saat ini
        $account = JurnalAccount::updateOrCreate(
            ['jurnal_id' => $accountData['id']],
            [
                'name' => $accountData['name'],
                'number' => $accountData['number'],
                'category' => $accountData['category'],
                'category_id' => $accountData['category_id'],
                'is_parent' => $accountData['is_parent'] ?? false,
                'indent' => $accountData['indent'] ?? 0,
                'parent_id' => $parentId,
                'balance_amount' => $accountData['balance_amount'] ?? 0,
                'synced_at' => now(),
            ]
        );
        $this->syncAccountCat($accountData['category_id'], $accountData['category']);
        // 2. Proses 'children' jika ada
        // JSON Anda menunjukkan 'children' bisa 'null', jadi !empty() sudah aman
        if (!empty($accountData['children'])) {
            foreach ($accountData['children'] as $childWrapper) {
                // Respons API Anda membungkus data anak di dalam kunci 'account'
                $childData = $childWrapper['account'] ?? null;
                if ($childData) {
                    // Panggil fungsi ini lagi (rekursif) untuk si anak
                    // dengan parent_id adalah ID dari akun yang baru saja kita simpan
                    $this->syncAccountAndChildren($childData, $account->id);
                }
            }
        }
    }
}
