<?php

namespace App\Services\Jurnal;

use Carbon\Carbon;
use Throwable;

class GeneralLedgerService extends JurnalBaseService
{
        public function calculateMonthlySales(array $ledgerData): array
        {
        $monthlySales = array_fill(0, 12, 0.0);

        $accounts = $ledgerData['accounts'] ?? [];
        $salesAccount = null;
        foreach ($accounts as $account) {
            if ($account['account_name'] === '(4100.0001) Pendapatan Jasa') {
                $salesAccount = $account;
                break;
            }
        }
        if ($salesAccount) {
            $transactions = $salesAccount['transactions'] ?? [];
            foreach ($transactions as $item) {
                $transaction = $item['transaction'] ?? null;
                if (!$transaction) continue;

                try {
                    $dateStr = $transaction['date'];
                    $month = (int) Carbon::createFromFormat('d/m/Y', $dateStr)->format('m');
                    $monthIndex = $month - 1;
                    if ($monthIndex >= 0 && $monthIndex < 12) {
                        $monthlySales[$monthIndex] += (float) $transaction['credit_raw'];
                    }

                } catch (\Exception $e) {
                    logger()->warning('Gagal parse tanggal transaksi ledger', ['date' => $dateStr ?? null]);
                }
            }
        }
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $randomData = [];
        for ($i = 0; $i < 12; $i++) {
            $randomData[] = rand(50000000, 500000000);
        }
        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Sales',
                    'type' => 'bar',
                    'data' => $monthlySales,
                    'itemStyle' => [
                        'color' => new \stdClass() 
                    ]
                ],
                [
                    'name' => 'Target', 
                    'type' => 'bar',
                    'data' => $randomData,
                    'itemStyle' => [
                        'color' => new \stdClass()
                    ]
                ]
            ]
        ];
        }
    public function getSummary(string $startDate, string $endDate): array
    {
        try {
            $params = [
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date'   => Carbon::parse($endDate)->format('d/m/Y'),
            ];

            $response = $this->get('general_ledger', $params);

            return $this->processReport($response);

        } catch (Throwable $e) {
            logger()->error('Gagal mengambil General Ledger dari Jurnal API', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Gagal terhubung ke Jurnal API: ' . $e->getMessage());
        }
    }

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
            $beginningBalance = $account['beginning_balance']['balance_raw'] ?? 0;
            $debit = $account['ending_balance']['debit_raw'] ?? 0;
            $credit = $account['ending_balance']['credit_raw'] ?? 0;
            $endingBalance = $account['ending_balance']['balance_raw'] ?? 0;

            $totalBeginning += $beginningBalance;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $totalEnding += $endingBalance;

            $processedAccounts[] = [
                'account_name' => $account['subheader'],
                'beginning_balance' => $beginningBalance,
                'debit' => $debit,
                'credit' => $credit,
                'ending_balance' => $endingBalance,
                'transactions' => $account['content'] ?? [], // Detail transaksi
            ];
        }

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
