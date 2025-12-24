<?php

namespace App\Services\Jurnal;

use App\Models\JurnalSalesInvoice;
use App\Models\JurnalSalesInvoiceCustomField;
use App\Models\JurnalSalesInvoiceTag;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncSalesInvoiceService
{
    private string $baseUrl;
    private string $accessToken;
    private int $perPage = 100;

    public function __construct()
    {
        $this->baseUrl = config('services.jurnal.base_url', 'https://api.jurnal.id/partner/core/api/v1');
        $this->accessToken = config('services.jurnal.api_key');
    }

    /**
     * Sinkronisasi semua halaman sales invoices dari Jurnal API
     */
    public function syncAllSalesInvoices(string $startDate = '1/1/2020'): array
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
                $response = $this->fetchSalesInvoices($page, $startDate);

                if (! $response['success']) {
                    $results['errors'][] = "Gagal mengambil data halaman {$page}:  {$response['error']}";
                    $page++;
                    continue;
                }

                $data = $response['data'];
                $pageInfo = $data['sales_lists']['page_info'] ?? [];
                $totalPages = $pageInfo['total_pages'] ?? 1;

                foreach ($data['sales_lists']['transactions'] ??  [] as $transaction) {
                    try {
                        $this->saveSalesInvoice($transaction);
                        $results['success']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Error pada transaksi {$transaction['transaction_no']}: {$e->getMessage()}";
                        Log::error("Jurnal Sales Invoice Sync Error: {$e->getMessage()}", [
                            'transaction_no' => $transaction['transaction_no'],
                            'transaction_id' => $transaction['id'],
                        ]);
                    }
                }

                $results['total'] = $pageInfo['result_size'] ?? 0;
                $page++;
            }
        } catch (\Exception $e) {
            $results['errors'][] = "Error umum: {$e->getMessage()}";
            Log::error("Jurnal Sales Invoice Sync General Error: {$e->getMessage()}");
        }

        return $results;
    }

    /**
     * Fetch sales invoices dari Jurnal API
     */
    private function fetchSalesInvoices(int $page = 1, string $startDate = '1/1/2020'): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/sales_lists", [
                'access_token' => $this->accessToken,
                'start_date' => $startDate,
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
     * Simpan atau update sales invoice
     */
    private function saveSalesInvoice(array $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $invoice = JurnalSalesInvoice::updateOrCreate(
                ['jurnal_id' => $transaction['id']],
                [
                    'transaction_no' => $transaction['transaction_no'],
                    'transaction_date' => $this->parseDate($transaction['transaction_date']),
                    'due_date' => $transaction['due_date'] ?  $this->parseDate($transaction['due_date']) : null,
                    'expiry_date' => $transaction['expiry_date'] ? $this->parseDate($transaction['expiry_date']) : null,

                    // Transaction Type & Status
                    'transaction_type_id' => $transaction['transaction_type']['id'] ?? null,
                    'transaction_type_name' => $transaction['transaction_type']['name'] ?? null,
                    'transaction_status_id' => $transaction['transaction_status']['id'] ??  null,
                    'transaction_status_name' => $transaction['transaction_status']['name_bahasa'] ?? $transaction['transaction_status']['name'] ?? null,

                    // Customer Info
                    'customer_id' => $transaction['person']['id'] ?? null,
                    'customer_name' => $transaction['person']['name'] ?? null,
                    'customer_type' => $transaction['person']['type'] ?? null,
                    'person_company_name' => $transaction['person_company_name'] ?? null,
                    'person_tax_no' => $transaction['person_tax_no'] ?? null,
                    'person_mobile' => $transaction['person_mobile'] ?? null,
                    'person_phone' => $transaction['person_phone'] ??  null,

                    // Address & Contact
                    'email' => $transaction['email'] ?? null,
                    'billing_address' => $transaction['billing_address'] ?? null,
                    'shipping_address' => $transaction['shipping_address'] ??  null,

                    // Reference & Notes
                    'reference_no' => $transaction['reference_no'] ?? null,
                    'memo' => $transaction['memo'] ??  null,
                    'message' => $transaction['message'] ??  null,

                    // Warehouse & Product
                    'warehouse_id' => $transaction['warehouse']['id'] ?? null,
                    'warehouse_name' => $transaction['warehouse']['name'] ?? null,
                    'product_id' => $transaction['product']['id'] ?? null,
                    'product_name' => $transaction['product']['name'] ?? null,
                    'product_code' => $transaction['product_code'] ?? null,

                    // Item Details
                    'quantity_unit' => (int)($transaction['quantity_unit'] ?? 0),
                    'product_unit_name' => $transaction['product_unit_name'] ??  null,
                    'unit_price' => (float)($transaction['unit_price'] ?? 0),
                    'discount_line_rate' => $this->parsePercentage($transaction['discount_line_rate'] ?? '0'),
                    'tax_rate' => (int)($transaction['tax_rate'] ?? 0),
                    'line_tax_amount' => (float)($transaction['line_tax_amount'] ?? 0),
                    'taxable_amount_per_line' => (float)($transaction['taxable_amount_per_line'] ?? 0),
                    'total_per_line' => (float)($transaction['total_per_line'] ?? 0),
                    'description' => $transaction['description'] ??  null,

                    // Amount Fields
                    'original_amount' => (float)($transaction['original_amount'] ?? 0),
                    'gross_taxable_amount' => (float)($transaction['gross_taxable_amount'] ?? 0),
                    'tax_amount' => (float)($transaction['tax_amount'] ?? 0),
                    'discount' => (float)($transaction['discount'] ?? 0),
                    'discount_rate_percentage' => (float)($transaction['discount_rate_percentage'] ?? 0),
                    'shipping_fee' => (float)($transaction['shipping_fee'] ?? 0),
                    'witholding_amount' => (float)($transaction['witholding_amount'] ?? 0),

                    // Payment Info
                    'payment' => (float)($transaction['payment'] ?? 0),
                    'total_paid' => (float)($transaction['total_paid'] ?? 0),
                    'balance_due' => (float)($transaction['balance_due'] ?? 0),
                    'deposit_all_payment' => (float)($transaction['deposit_all_payment'] ?? 0),
                    'payment_method_name' => $transaction['payment_method_name'] ?? null,

                    // Additional Info
                    'total_return_amount' => (float)($transaction['total_return_amount'] ?? 0),
                    'total_invoice' => (float)($transaction['total_invoice'] ??  0),
                    'withholding_type' => $transaction['withholding_type'] ??  null,
                    'sales_order_no' => $transaction['sales_order_no'] ??  null,
                    'sales_invoice_no' => $transaction['sales_invoice_no'] ?? null,

                    // Currency
                    'currency_code' => $transaction['currency'][0]['currency_code'] ?? 'IDR',
                    'currency_list_id' => (int)($transaction['currency_list_id'] ?? 0),
                    'mc_rate' => (float)($transaction['currency'][0]['mc_rate'] ??  1),

                    // Account
                    'account_id' => $transaction['account']['id'] ?? null,
                    'account_number' => $transaction['account']['number'] ?? null,
                    'account_name' => $transaction['account']['name'] ?? null,

                    // Status Flags
                    'hidden_transaction' => (bool)($transaction['hidden_transaction_type_id'] ?? false),
                    'hidden_transaction_type_id' => (int)($transaction['hidden_transaction_type_id'] ?? 0),

                    'sync_status' => 'synced',
                    'last_sync_at' => now(),
                ]
            );

            // Simpan custom fields
            if (! empty($transaction['custom_fields'])) {
                // Hapus custom fields lama
                JurnalSalesInvoiceCustomField::where('jurnal_sales_invoice_id', $invoice->id)->delete();

                foreach ($transaction['custom_fields'] as $field) {
                    JurnalSalesInvoiceCustomField::updateOrCreate(
                        [
                            'jurnal_sales_invoice_id' => $invoice->id,
                            'field_name' => $field['field_name'] ?? null,
                        ],
                        [
                            'field_value' => $field['field_value'] ?? null,
                        ]
                    );
                }
            }

            // Simpan tags
            if (! empty($transaction['tags'])) {
                // Hapus tags lama
                JurnalSalesInvoiceTag::where('jurnal_sales_invoice_id', $invoice->id)->delete();

                foreach ($transaction['tags'] as $tag) {
                    JurnalSalesInvoiceTag::updateOrCreate(
                        [
                            'jurnal_sales_invoice_id' => $invoice->id,
                            'tag_name' => $tag,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Parse format tanggal dari Jurnal (YYYY-MM-DD atau DD/MM/YYYY)
     */
    private function parseDate(string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Coba format YYYY-MM-DD dulu
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }

            // Coba format DD/MM/YYYY
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse percentage format (e.g., "10.5 %" to 10.5)
     */
    private function parsePercentage(string $percentage): float
    {
        // Remove "%" dan whitespace
        $cleaned = str_replace(['%', ' '], '', $percentage);
        return (float)$cleaned;
    }

    /**
     * Sinkronisasi sales invoice tertentu berdasarkan ID
     */
    public function syncSingleInvoice(int $invoiceId): bool
    {
        try {
            $response = Http::get("{$this->baseUrl}/sales_lists/{$invoiceId}", [
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->saveSalesInvoice($data);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to sync single invoice: {$e->getMessage()}", [
                'invoice_id' => $invoiceId,
            ]);
            return false;
        }
    }

    /**
     * Get statistics sync
     */
    public function getSyncStats(): array
    {
        return [
            'total' => JurnalSalesInvoice::count(),
            'synced' => JurnalSalesInvoice::where('sync_status', 'synced')->count(),
            'pending' => JurnalSalesInvoice::where('sync_status', 'pending')->count(),
            'failed' => JurnalSalesInvoice::where('sync_status', 'failed')->count(),
            'last_sync' => JurnalSalesInvoice::latest('last_sync_at')->first()?->last_sync_at,
        ];
    }
}
