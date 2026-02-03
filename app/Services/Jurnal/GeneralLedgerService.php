<?php

namespace App\Services\Jurnal;

use App\Models\AccountBudget;
use App\Models\BudgetReport;
use App\Models\BudgetReportAmount;
use App\Models\BudgetReportLine;
use Carbon\Carbon;
use Throwable;

class GeneralLedgerService extends JurnalBaseService
{
    public function lineInOutData(array $ledgerData, $year)
    {
        // Struktur bulan (untuk X Axis)
        $months = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        ];

        // Penampung data IN / OUT per bulan
        $monthIn = array_fill(0, 12, 0.0);
        $monthOut = array_fill(0, 12, 0.0);

        // Ambil daftar akun
        $accounts = $ledgerData['accounts'] ?? [];

        // Cari akun in-out
        $inoutAccount = collect($accounts)
            ->firstWhere('account_name', '(1111.1003) Mandiri Cab. Depok - Rp');

        if ($inoutAccount) {
            $transactions = $inoutAccount['transactions'] ?? [];

            foreach ($transactions as $item) {

                $transaction = $item['transaction'] ?? null;
                if (!$transaction || empty($transaction['date'])) {
                    continue;
                }

                $dateStr = $transaction['date'];
                $carbonDate = null;

                // Parsing tanggal lebih aman
                try {
                    // Format umum di ledger: d/m/Y
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateStr)) {
                        $carbonDate = Carbon::createFromFormat('d/m/Y', $dateStr);
                    } else {
                        // Fallback format
                        $carbonDate = Carbon::parse($dateStr);
                    }
                } catch (\Throwable $e) {
                    logger()->warning('Gagal parse tanggal transaksi ledger', ['date' => $dateStr]);
                    continue;
                }

                // Pastikan tahun sesuai filter
                if ($carbonDate->year != $year) {
                    continue;
                }

                $monthIndex = $carbonDate->month - 1;

                // Tambahkan nilai debit (IN) & credit (OUT)
                $monthIn[$monthIndex]  += (float) ($transaction['debit_raw']  ?? 0);
                $monthOut[$monthIndex] += (float) ($transaction['credit_raw'] ?? 0);
            }
        }

        // Return siap pakai untuk ECharts
        return [
            'months' => $months,
            'in'     => $monthIn,
            'out'    => $monthOut,
        ];
    }

    public function calculateMonthlySalesBU(array $ledgerData, $year): array
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
        $totalAcccountSales = 0;
        if ($salesAccount) {
            $totalAcccountSales = $salesAccount['credit'];
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
        $budget = [
            AccountBudget::where('year', $year)->sum('budget_jan'),
            AccountBudget::where('year', $year)->sum('budget_feb'),
            AccountBudget::where('year', $year)->sum('budget_mar'),
            AccountBudget::where('year', $year)->sum('budget_apr'),
            AccountBudget::where('year', $year)->sum('budget_mei'),
            AccountBudget::where('year', $year)->sum('budget_jun'),
            AccountBudget::where('year', $year)->sum('budget_jul'),
            AccountBudget::where('year', $year)->sum('budget_ags'),
            AccountBudget::where('year', $year)->sum('budget_sep'),
            AccountBudget::where('year', $year)->sum('budget_okt'),
            AccountBudget::where('year', $year)->sum('budget_nov'),
            AccountBudget::where('year', $year)->sum('budget_des'),
        ];
        $randomData = $budget;

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
            ],
            'summary' => [
                "sales" => $totalAcccountSales,
                "budget" => array_sum($randomData)
            ]
        ];
    }
    public function calculateMonthlySales(array $ledgerData, $year): array
    {
        // =========================
        // SALES (LEDGER)
        // =========================
        $monthlySales = array_fill(0, 12, 0.0);

        $accounts = $ledgerData['accounts'] ?? [];
        $salesAccount = collect($accounts)
            ->firstWhere('account_name', '(4100.0001) Pendapatan Jasa');

        $totalAcccountSales = 0;

        if ($salesAccount) {
            $totalAcccountSales = (float) ($salesAccount['credit'] ?? 0);

            foreach ($salesAccount['transactions'] ?? [] as $item) {
                $transaction = $item['transaction'] ?? null;
                if (!$transaction) continue;

                try {
                    $monthIndex = Carbon::createFromFormat('d/m/Y', $transaction['date'])
                        ->month - 1;

                    if ($monthIndex >= 0 && $monthIndex < 12) {
                        $monthlySales[$monthIndex] += (float) $transaction['credit_raw'];
                    }
                } catch (\Throwable $e) {
                    logger()->warning('Gagal parse tanggal transaksi ledger', [
                        'date' => $transaction['date'] ?? null
                    ]);
                }
            }
        }

        // =========================
        // BUDGET (REVENUE ONLY)
        // =========================
        $budget = array_fill(0, 12, 0.0);

        $budgetReport = BudgetReport::where('budget_year', $year)->first();

        if ($budgetReport) {
            $revenueLineIds = BudgetReportLine::where('budget_report_id', $budgetReport->id)
                ->where('name', 'Revenue')
                ->pluck('id');

            if ($revenueLineIds->isNotEmpty()) {
                $amounts = BudgetReportAmount::whereIn('amountable_id', function ($q) use ($revenueLineIds) {
                    $q->select('id')
                        ->from('budget_report_line_childrens')
                        ->whereIn('budget_report_line_id', $revenueLineIds);
                })
                    ->where('amountable_type', 'App\Models\BudgetReportLineChildren')
                    ->selectRaw('period_index, SUM(amount) as total')
                    ->groupBy('period_index')
                    ->get();

                foreach ($amounts as $row) {
                    if ($row->period_index >= 0 && $row->period_index < 12) {
                        $budget[$row->period_index] = (float) $row->total;
                    }
                }
            }
        }

        // =========================
        // RESPONSE
        // =========================
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

        return [
            'labels' => $labels,
            'series' => [

                [
                    'name' => 'Target',
                    'type' => 'bar',
                    'data' => $budget,
                    'itemStyle' => ['color' => new \stdClass()],
                ],
                [
                    'name' => 'Sales',
                    'type' => 'bar',
                    'data' => $monthlySales,
                    'itemStyle' => ['color' => new \stdClass()],
                ]
            ],
            'summary' => [
                'sales' => $totalAcccountSales,
                'budget' => array_sum($budget),
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
