<?php

namespace App\Services\Jurnal;

use App\Models\JurnalCreditMemo;
use App\Models\JurnalCreditMemoLine; // Pastikan buat Model ini
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncCreditMemoService
{
    private string $baseUrl;
    private string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.jurnal.base_url', 'https://api.jurnal.id/partner/core/api/v1');
        $this->accessToken = config('services.jurnal.api_key');
    }

    /**
     * Sinkronisasi Credit Memos (Mark & Sweep)
     */
    public function sync(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'deleted' => 0,
            'total' => 0,
            'errors' => [],
        ];

        // 1. MARK: Tentukan waktu batch
        $syncBatchTime = now();

        try {
            $page = 1;
            $totalPages = 1;

            Log::info("🚀 Memulai sinkronisasi Credit Memos...");

            while ($page <= $totalPages) {
                // Fetch API
                $response = $this->fetchCreditMemos($page);

                if (!$response['success']) {
                    throw new \Exception("Gagal mengambil halaman {$page}: {$response['error']}");
                }

                $data = $response['data'];
                $totalPages = $data['total_pages'] ?? 1;
                $creditMemos = $data['credit_memos'] ?? [];

                if (empty($creditMemos)) {
                    break;
                }

                foreach ($creditMemos as $cmData) {
                    try {
                        $this->saveCreditMemo($cmData, $syncBatchTime);
                        $results['success']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $errorMsg = "Error Transaction {$cmData['transaction_no']}: {$e->getMessage()}";
                        $results['errors'][] = $errorMsg;
                        Log::error($errorMsg);
                    }
                }

                $results['total'] = $data['total_count'] ?? 0;
                $page++;
            }

            // 2. SWEEP: Hapus data usang
            $results['deleted'] = $this->pruneStaleData($syncBatchTime);
        } catch (\Exception $e) {
            $results['errors'][] = "Fatal Error: {$e->getMessage()}";
            Log::error("Jurnal Credit Memo Sync Error: {$e->getMessage()}");
        }

        return $results;
    }

    private function fetchCreditMemos(int $page): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/credit_memos", [
                'access_token' => $this->accessToken,
                'page' => $page,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => "HTTP {$response->status()}: {$response->body()}"];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function saveCreditMemo(array $data, Carbon $syncTime): void
    {
        DB::transaction(function () use ($data, $syncTime) {
            // 1. Update/Create Parent
            $creditMemo = JurnalCreditMemo::updateOrCreate(
                ['jurnal_id' => $data['id']],
                [
                    'transaction_no' => $data['transaction_no'],
                    'transaction_date' => $this->parseDate($data['transaction_date']),

                    // Person / Customer
                    'person_id' => $data['person']['id'] ?? null,
                    'person_name' => $data['person']['person_name'] ?? null,

                    // Status
                    'status' => $data['transaction_status']['name'] ?? null,
                    'status_bahasa' => $data['transaction_status']['name_bahasa'] ?? null,

                    // Amounts
                    'original_amount' => (float) ($data['original_amount'] ?? 0),
                    'remaining_amount' => (float) ($data['remaining'] ?? 0),
                    'witholding_amount' => (float) ($data['witholding_amount'] ?? 0),

                    // Currency
                    'currency_code' => $data['currency_code'] ?? 'IDR',
                    'currency_rate' => (float) ($data['currency_rate'] ?? 1),

                    // Details
                    'memo' => $data['memo'] ?? null,
                    'tags' => $data['tags_string'] ?? null,

                    // Mark Sync
                    'sync_status' => 'synced',
                    'last_sync_at' => $syncTime,
                ]
            );

            // 2. Sync Lines (Delete Insert Strategy for clean child records)
            // Mengambil dari 'transaction_account_lines_attributes'
            $lines = $data['transaction_account_lines_attributes'] ?? [];

            // Hapus line lama
            JurnalCreditMemoLine::where('jurnal_credit_memo_id', $creditMemo->id)->delete();

            if (!empty($lines) && is_array($lines)) {
                foreach ($lines as $line) {
                    JurnalCreditMemoLine::create([
                        'jurnal_credit_memo_id' => $creditMemo->id,
                        'jurnal_line_id' => $line['id'] ?? null,

                        // Account Info
                        'account_id' => $line['account']['id'] ?? null,
                        'account_name' => $line['account']['name'] ?? null,
                        'account_number' => $line['account']['number'] ?? null,

                        'description' => $line['description'] ?? null,

                        // Values
                        'debit' => (float) ($line['debit'] ?? 0),
                        'credit' => (float) ($line['credit'] ?? 0),
                    ]);
                }
            }
        });
    }

    private function pruneStaleData(Carbon $syncBatchTime): int
    {
        $query = JurnalCreditMemo::where('last_sync_at', '<', $syncBatchTime)
            ->orWhereNull('last_sync_at');

        $count = $query->count();
        if ($count > 0) {
            $query->delete();
            Log::info("🧹 Membersihkan {$count} Credit Memos yang sudah terhapus di Jurnal.");
        }
        return $count;
    }

    private function parseDate($date): ?string
    {
        if (empty($date)) return null;
        try {
            // Coba format DD/MM/YYYY
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                // Coba format YYYY-MM-DD
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $x) {
                return null;
            }
        }
    }
}
