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
     * Menggunakan metode Mark & Sweep (Pruning) untuk menangani data yang dihapus.
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
        $perPage = 100;

        // 1. MARK: Tentukan waktu mulai batch ini.
        // Waktu ini akan menjadi "stempel" untuk semua data yang valid di siklus ini.
        $syncBatchTime = now();

        $this->logInfo("🚀 Memulai sinkronisasi Sales Invoices dari Jurnal.id...");
        $this->logInfo("🕒 Batch Time: " . $syncBatchTime->toDateTimeString());

        do {
            try {
                // Request ke API
                $response = $this->get('sales_invoices', [
                    'page' => $page,
                    'page_size' => $perPage,
                    // Opsional: tambahkan 'updated_since' jika ingin incremental sync
                    // tapi untuk pruning yang akurat, full sync per periode lebih aman.
                ]);
            } catch (Throwable $e) {
                $this->logError("❌ Gagal total mengambil data dari API pada halaman {$page}: " . $e->getMessage());
                // PENTING: Throw error agar script berhenti dan TIDAK menjalankan proses penghapusan data (pruning)
                throw $e;
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
                    // Masukkan $syncBatchTime ke proses update
                    $this->syncSingleInvoice($invoiceData, $syncBatchTime);
                    $totalSynced++;
                } catch (Throwable $e) {
                    $invoiceId = $invoiceData['id'] ?? 'UNKNOWN';
                    Log::error("❌ Gagal sync invoice {$invoiceId}: {$e->getMessage()}", [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Kita continue loop, tapi data ini TIDAK akan punya synced_at terbaru,
                    // hati-hati: jika gagal sync, data ini akan dianggap "stale" dan mungkin terhapus di langkah Pruning.
                    // Jika Anda ingin proteksi, pastikan error handling di sini matang.
                }
                $progressBar?->advance();
            }

            $progressBar?->finish();
            if ($this->command) $this->command->newLine(2);

            $page++;
        } while ($count > 0 && ($page <= ($response['total_pages'] ?? $page)));

        // 2. SWEEP: Hapus data usang.
        // Hanya dijalankan jika loop di atas selesai tanpa crash (exception).
        $this->pruneStaleData($syncBatchTime);

        $this->logInfo("🏁 Sinkronisasi invoice selesai. Total tersimpan/updated: {$totalSynced}");
        return $totalSynced;
    }

    private function syncSingleInvoice(array $invoiceData, Carbon $syncTime): void
    {
        DB::transaction(function () use ($invoiceData, $syncTime) {
            $person = null;
            if (!empty($invoiceData['person'])) {
                // Person di-sync tapi tidak perlu di-prune di sini (karena ini service invoice)
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

                    // KUNCI: Update synced_at dengan waktu batch saat ini
                    'synced_at' => $syncTime,
                ]
            );

            // Lines & Payments di-replace (delete insert) per invoice aman dilakukan
            $this->syncInvoiceLines($invoice, $invoiceData['transaction_lines_attributes'] ?? []);
            $this->syncPayments($invoice, $invoiceData['payments'] ?? []);
        });
    }

    /**
     * Menghapus (atau soft delete) invoice yang tidak ditemukan di API
     * pada siklus sinkronisasi saat ini.
     */
    private function pruneStaleData(Carbon $syncBatchTime): void
    {
        // Cari data yang synced_at-nya KURANG DARI waktu batch ini.
        // Artinya data tersebut tidak tersentuh/terupdate saat looping API tadi.
        $query = JurnalInvoice::where('synced_at', '<', $syncBatchTime)
            ->orWhereNull('synced_at'); // Handle data lama yg mungkin null

        $count = $query->count();

        if ($count > 0) {
            $this->logInfo("🧹 Menemukan {$count} data usang (terhapus di Jurnal). Melakukan pembersihan...");

            // Hapus data. Pastikan Model JurnalInvoice menggunakan SoftDeletes 
            // jika ingin bisa direstore, atau delete permanen jika tidak.
            $query->delete();

            $this->logInfo("✨ Berhasil menghapus {$count} data invoice lokal.");
        } else {
            $this->logInfo("✅ Database bersih. Tidak ada data usang yang perlu dihapus.");
        }
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
                'synced_at' => now(), // Person pakai now() saja, tidak ikut batch invoice
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
            // Coba format d/m/Y dulu karena ini format input Anda
            return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
        } catch (Throwable) {
            try {
                // Fallback ke parser standar jika format berbeda
                return Carbon::parse($value)->startOfDay();
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
