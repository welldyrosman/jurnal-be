<?php

namespace App\Services\Jurnal;

use Carbon\Carbon;
use Throwable;

/**
 * Service untuk mengambil dan memproses laporan General Ledger (Buku Besar) dari Jurnal.id.
 * Tidak untuk sinkronisasi, tetapi untuk proxy API langsung.
 */
class GeneralLedgerService extends JurnalBaseService
{
    /**
     * Mengambil dan merangkum laporan General Ledger.
     *
     * @param string $startDate (Format YYYY-MM-DD)
     * @param string $endDate   (Format YYYY-MM-DD)
     * @return array
     * @throws \Exception
     */
    public function getSummary(string $startDate, string $endDate): array
    {
        try {
            // Konversi format tanggal untuk Jurnal API
            $params = [
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date'   => Carbon::parse($endDate)->format('d/m/Y'),
            ];

            // Panggil API Jurnal
            $response = $this->get('general_ledger', $params);

            // Proses data mentah menjadi ringkasan
            return $this->processReport($response);

        } catch (Throwable $e) {
            // Tangani error dari Guzzle atau Jurnal
            logger()->error('Gagal mengambil General Ledger dari Jurnal API', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Gagal terhubung ke Jurnal API: ' . $e->getMessage());
        }
    }

    /**
     * Memproses respons JSON mentah dari Jurnal menjadi ringkasan dashboard.
     *
     * @param array $response
     * @return array
     */
    private function processReport(array $response): array
    {
        $reportData = $response['general_ledger'] ?? [];
        $accounts = $reportData['report']['accounts'] ?? [];

        $totalBeginning = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $totalEnding = 0;

        $processedAccounts = [];

        foreach ($accounts as $account) {
            // Ambil nilai-nilai ..._raw untuk kalkulasi
            $beginningBalance = $account['beginning_balance']['balance_raw'] ?? 0;
            $debit = $account['ending_balance']['debit_raw'] ?? 0;
            $credit = $account['ending_balance']['credit_raw'] ?? 0;
            $endingBalance = $account['ending_balance']['balance_raw'] ?? 0;

            // Akumulasi total
            $totalBeginning += $beginningBalance;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $totalEnding += $endingBalance;

            // Simpan data akun yang sudah diproses (opsional, tapi berguna)
            $processedAccounts[] = [
                'account_name' => $account['subheader'],
                'beginning_balance' => $beginningBalance,
                'debit' => $debit,
                'credit' => $credit,
                'ending_balance' => $endingBalance,
                'transactions' => $account['content'] ?? [], // Detail transaksi
            ];
        }

        // Kembalikan sebagai struktur JSON yang rapi untuk Vue
        return [
            'summary' => [
                'period' => $reportData['header']['period'] ?? '',
                'currency' => $reportData['header']['currency'] ?? '',
                'total_beginning_balance' => $totalBeginning,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'total_ending_balance' => $totalEnding,
                'total_movement' => $totalDebit + $totalCredit,
                'total_accounts' => count($accounts),
            ],
            'accounts' => $processedAccounts,
        ];
    }
}
