<?php

namespace App\Services\Jurnal;

use App\Models\JurnalInvoice;
use App\Models\JurnalInvoiceLine;
use App\Models\JurnalPayment;
use App\Models\JurnalPerson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SyncInvoicesService extends JurnalBaseService
{
    private ?Command $command = null;

    /**
     * Sinkronisasi data invoice dari Jurnal.id
     *
     * @param Command|null $command
     * @return integer
     * @throws Throwable
     */
    public function sync(Command $command = null): int
    {
        $this->command = $command;
        $page = 1;
        $totalSynced = 0;
        $perPage = 100; // Ambil 100 data per halaman untuk efisiensi

        $this->logInfo("🚀 Memulai sinkronisasi Sales Invoices dari Jurnal.id...");

        do {
            try {
                $response = $this->get('sales_invoices', [
                    'page' => $page,
                    'page_size' => $perPage,
                ]);
            } catch (Throwable $e) {
                $this->logError("❌ Gagal total mengambil data dari API pada halaman {$page}: " . $e->getMessage());
                throw $e; // Hentikan proses jika API gagal total
            }

            $invoices = $response['sales_invoices'] ?? [];
            $count = count($invoices);

            if ($count === 0) {
                $this->logInfo("✅ Tidak ada data lagi pada halaman {$page}.");
                break;
            }

            $this->logInfo("📦 Memproses halaman {$page} (jumlah data: {$count})");
            $progressBar = $this->command ? $this->command->getOutput()->createProgressBar($count) : null;
            $progressBar?->start();

            foreach ($invoices as $invoiceData) {
                try {
                    $this->syncSingleInvoice($invoiceData);
                    $totalSynced++;
                } catch (Throwable $e) {
                    $invoiceId = $invoiceData['id'] ?? 'UNKNOWN';
                    Log::error("❌ Gagal sync invoice {$invoiceId}: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                $progressBar?->advance();
            }

            $progressBar?->finish();
            if ($this->command) $this->command->newLine(2);

            $page++;
        } while ($count > 0 && ($page <= ($response['total_pages'] ?? $page))); // Berhenti jika halaman sudah habis

        $this->logInfo("🏁 Sinkronisasi invoice selesai. Total tersimpan: {$totalSynced}");
        return $totalSynced;
    }

    private function syncSingleInvoice(array $invoiceData): void
    {
        DB::transaction(function () use ($invoiceData) {
            $person = null;
            if (!empty($invoiceData['person'])) {
                $person = $this->syncPerson($invoiceData['person']);
            }

            $invoice = JurnalInvoice::updateOrCreate(
                ['jurnal_id' => $invoiceData['id']],
                [
                    'person_id' => $person?->id,
                    'transaction_no' => $invoiceData['transaction_no'],
                    'status' => $invoiceData['status'],
                    'source' => $invoiceData['source'],
                    'address' => $invoiceData['address'],
                    'message' => $invoiceData['message'],
                    'memo' => $invoiceData['memo'],
                    'shipping_address' => $invoiceData['shipping_address'],
                    'is_shipped' => $invoiceData['is_shipped'],
                    'reference_no' => $invoiceData['reference_no'],
                    'subtotal' => $invoiceData['subtotal'],
                    'discount_price' => $invoiceData['discount_price'],
                    'tax_amount' => $invoiceData['tax_amount'],
                    'shipping_price' => $invoiceData['shipping_price'],
                    'total_amount' => $invoiceData['original_amount'],
                    'payment_received' => $invoiceData['payment_received_amount'],
                    'remaining' => $invoiceData['remaining'],
                    'deposit' => $invoiceData['deposit'],
                    'term_name' => $invoiceData['term']['name'] ?? null,
                    'transaction_status_name' => $invoiceData['transaction_status']['name_bahasa'] ?? null,
                    'currency_code' => $invoiceData['currency_code'] ?? 'IDR',
                    'transaction_date' => $this->parseDate($invoiceData['transaction_date']),
                    'due_date' => $this->parseDate($invoiceData['due_date']),
                    'shipping_date' => $this->parseDate($invoiceData['shipping_date']),
                    'created_at_jurnal' => Carbon::parse($invoiceData['created_at']),
                    'updated_at_jurnal' => Carbon::parse($invoiceData['updated_at']),
                    'deleted_at_jurnal' => $this->parseDate($invoiceData['deleted_at']),
                    'raw_data' => json_encode($invoiceData),
                    'synced_at' => now(),
                ]
            );

            $this->syncInvoiceLines($invoice, $invoiceData['transaction_lines_attributes'] ?? []);
            $this->syncPayments($invoice, $invoiceData['payments'] ?? []);
        });
    }

    private function syncPerson(array $personData): JurnalPerson
    {
        return JurnalPerson::updateOrCreate(
            ['jurnal_id' => $personData['id']],
            [
                'display_name' => $personData['display_name'],
                'email' => $personData['email'],
                'phone' => $personData['phone'],
                'address' => $personData['address'],
                'billing_address' => $personData['billing_address'],
                'synced_at' => now(),
            ]
        );
    }

    private function syncInvoiceLines(JurnalInvoice $invoice, array $lines): void
    {
        $invoice->lines()->delete();
        if (empty($lines)) return;

        $linesData = collect($lines)->map(fn($line) => [
            'invoice_id' => $invoice->id,
            'jurnal_id' => $line['id'],
            'product_name' => $line['product']['name'] ?? null,
            'product_jurnal_id' => $line['product']['id'] ?? null,
            'description' => $line['description'],
            'quantity' => $line['quantity'],
            'rate' => $line['rate'],
            'amount' => $line['amount'],
            'unit_name' => $line['unit']['name'] ?? null,
            'tax_name' => $line['line_tax']['name'] ?? null,
            'tax_rate' => $line['line_tax']['rate'] ?? null,
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        JurnalInvoiceLine::insert($linesData);
    }

    private function syncPayments(JurnalInvoice $invoice, array $payments): void
    {
        $invoice->payments()->delete();
        if (empty($payments)) return;

        $paymentsData = collect($payments)->map(fn($pay) => [
            'invoice_id' => $invoice->id,
            'jurnal_id' => $pay['id'],
            'transaction_no' => $pay['transaction_no'],
            'transaction_date' => $this->parseDate($pay['transaction_date']),
            'amount' => $pay['amount'],
            'payment_method_name' => $pay['payment_method_name'],
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        JurnalPayment::insert($paymentsData);
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            try {
                return Carbon::createFromFormat('d/m/Y', $value);
            } catch (Throwable) {
                return null;
            }
        }
    }

    private function logInfo(string $message): void
    {
        Log::info($message);
        $this->command?->info($message);
    }

    private function logError(string $message): void
    {
        Log::error($message);
        $this->command?->error($message);
    }
}

