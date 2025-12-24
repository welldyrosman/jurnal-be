<?php

namespace App\Services\Jurnal;

use App\Models\JurnalReceivePayment;
use App\Models\JurnalReceivePaymentRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncPaymentService
{
    private string $baseUrl;
    private string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.jurnal.base_url', 'https://api.jurnal.id/partner/core/api/v1');
        $this->accessToken = config('services.jurnal.api_key');
    }

    /**
     * Sinkronisasi semua halaman receive payments dari Jurnal API
     */
    public function syncAllReceivePayments(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => 0,
            'errors' => [],
        ];

        try {
            $page = 1;
            $totalPages = 1;

            while ($page <= $totalPages) {
                $response = $this->fetchReceivePayments($page);

                if (! $response['success']) {
                    $results['errors'][] = "Gagal mengambil data halaman {$page}:  {$response['error']}";
                    $page++;
                    continue;
                }

                $data = $response['data'];
                $totalPages = $data['total_pages'] ?? 1;

                foreach ($data['receive_payments'] ??  [] as $payment) {
                    try {
                        $this->saveReceivePayment($payment);
                        $results['success']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Error pada transaksi {$payment['transaction_no']}: {$e->getMessage()}";
                    }
                }

                $results['total'] = $data['total_count'] ?? 0;
                $page++;
            }
        } catch (\Exception $e) {
            $results['errors'][] = "Error umum: {$e->getMessage()}";
            // \Log::error("Jurnal Sync General Error: {$e->getMessage()}");
        }

        return $results;
    }

    /**
     * Fetch receive payments dari Jurnal API
     */
    private function fetchReceivePayments(int $page = 1): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/receive_payments", [
                'access_token' => $this->accessToken,
                'page' => $page,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Simpan atau update receive payment dengan UPSERT untuk records
     */
    private function saveReceivePayment(array $payment): void
    {
        DB::transaction(function () use ($payment) {
            $receivePayment = JurnalReceivePayment::updateOrCreate(
                ['jurnal_id' => $payment['id']],
                [
                    'transaction_no' => $payment['transaction_no'],
                    'token' => $payment['token'] ?? null,
                    'memo' => $payment['memo'] ??  null,
                    'source' => $payment['source'] ?? 'import',
                    'custom_id' => $payment['custom_id'] ?? null,
                    'status' => $payment['status'],
                    'transaction_status_id' => $payment['transaction_status']['id'] ?? null,
                    'transaction_status_name' => $payment['transaction_status']['name_bahasa'] ?? $payment['transaction_status']['name'] ?? null,
                    'deleted_at' => $payment['deleted_at'] ??  null,
                    'deletable' => $payment['deletable'] ?? false,
                    'editable' => $payment['editable'] ?? false,
                    'audited_by' => $payment['audited_by'] ?? null,
                    'transaction_date' => $payment['transaction_date'] ? $this->parseDate($payment['transaction_date']) : null,
                    'due_date' => $payment['due_date'] ? $this->parseDate($payment['due_date']) : null,
                    'person_id' => $payment['person']['id'] ?? null,
                    'person_name' => $payment['person']['name'] ?? null,
                    'person_email' => $payment['person']['email'] ?? null,
                    'person_address' => $payment['person']['address'] ?? null,
                    'person_phone' => $payment['person']['phone'] ?? null,
                    'person_fax' => $payment['person']['fax'] ?? null,
                    'transaction_type_id' => $payment['transaction_type']['id'] ??  null,
                    'transaction_type_name' => $payment['transaction_type']['name'] ?? null,
                    'payment_method_id' => $payment['payment_method']['id'] ??  null,
                    'payment_method_name' => $payment['payment_method']['name'] ?? null,
                    'deposit_to_id' => $payment['deposit_to']['id'] ?? null,
                    'deposit_to_name' => $payment['deposit_to']['name'] ?? null,
                    'deposit_to_number' => $payment['deposit_to']['number'] ?? null,
                    'deposit_to_category' => $payment['deposit_to']['category']['name'] ?? null,
                    'is_draft' => $payment['is_draft'] ?? false,
                    'withholding_account_name' => $payment['witholding']['witholding_account_name'] ?? null,
                    'withholding_account_number' => $payment['witholding']['witholding_account_number'] ?? null,
                    'withholding_account_id' => $payment['witholding']['account_id'] ?? null,
                    'withholding_value' => (float)($payment['witholding']['value'] ?? 0),
                    'withholding_type' => $payment['witholding']['type'] ?? 'value',
                    'withholding_amount' => (float)($payment['witholding']['amount'] ?? 0),
                    'withholding_category_id' => $payment['witholding']['category']['id'] ?? null,
                    'original_amount' => (float)($payment['original_amount'] ?? 0),
                    'total' => (float)($payment['total'] ?? 0),
                    'currency_code' => $payment['currency_code'] ?? 'IDR',
                    'currency_list_id' => $payment['currency_list_id'] ?? null,
                    'currency_from_id' => $payment['currency_from_id'] ?? null,
                    'currency_to_id' => $payment['currency_to_id'] ?? null,
                    'multi_currency_id' => $payment['multi_currency_id'] ?? null,
                    'is_reconciled' => $payment['is_reconciled'] ?? false,
                    'is_create_before_conversion' => $payment['is_create_before_conversion'] ?? false,
                    'is_import' => $payment['is_import'] ?? true,
                    'import_id' => $payment['import_id'] ?? null,
                    'skip_at' => $payment['skip_at'] ?? false,
                    'disable_link' => $payment['disable_link'] ?? false,
                    'comments_size' => $payment['comments_size'] ?? 0,
                    'sync_status' => 'synced',
                    'last_sync_at' => now(),
                ]
            );

            // Simpan records dengan UPSERT (insert or update)
            if (!empty($payment['records'])) {
                foreach ($payment['records'] as $record) {
                    // ✅ GUNAKAN updateOrCreate UNTUK UPSERT
                    JurnalReceivePaymentRecord::updateOrCreate(
                        [
                            'jurnal_receive_payment_id' => $receivePayment->id,
                            'jurnal_record_id' => $record['id'],
                        ],
                        [
                            'jurnal_transaction_id' => $record['transaction_id'] ?? null,
                            'amount' => (float)($record['amount'] ?? 0),
                            'description' => $record['description'] ?? null,
                            'transaction_type_id' => $record['transaction_type_id'] ?? null,
                            'transaction_type' => $record['transaction_type'] ?? null,
                            'transaction_no' => $record['transaction_no'] ??  null,
                            'transaction_due_date' => $record['transaction_due_date'] ? $this->parseDate($record['transaction_due_date']) : null,
                            'transaction_total' => (float)($record['transaction_total'] ?? 0),
                            'transaction_balance_due' => (float)($record['transaction_balance_due'] ?? 0),
                        ]
                    );
                }
            }
        });
    }

    /**
     * Parse format tanggal dari Jurnal (DD/MM/YYYY)
     */
    private function parseDate(string $date): ?string
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sinkronisasi ulang data yang gagal
     */
    public function syncFailedPayments(): array
    {
        $failedPayments = JurnalReceivePayment::where('sync_status', 'failed')->get();

        $results = [
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($failedPayments as $payment) {
            try {
                $payment->update([
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'last_sync_at' => now(),
                ]);
                $results['success']++;
            } catch (\Exception $e) {
                $payment->update([
                    'sync_error' => $e->getMessage(),
                ]);
                $results['failed']++;
            }
        }

        return $results;
    }
}
