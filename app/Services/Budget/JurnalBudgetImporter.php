<?php

namespace App\Services\Budget;

use App\Models\{
    BudgetReport,
    BudgetReportHeader,
    BudgetReportLine,
    BudgetReportLineChild,
    BudgetReportAccount,
    BudgetReportAmount,
    BudgetReportLineChildren
};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JurnalBudgetImporter
{
    private function parseDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    public function import(array $report, int $templateId): BudgetReport
    {
        return DB::transaction(function () use ($report, $templateId) {

            $year = Carbon::createFromFormat('d/m/Y', $report['start_date_range'])->year;
            // 🔥 delete data lama (per template)
            BudgetReport::where('template_id', $templateId)->where('budget_year', $year)->delete();

            // 1️⃣ report utama
            $budgetReport = BudgetReport::create([
                'template_id'       => $templateId,
                'budget_year'  => $year,
                'layout_name'       => $report['layout_name'],
                'start_date'        => $this->toDate($report['start_date_range']),
                'end_date'          => $this->toDate($report['end_date_range']),
                'budget_range'      => $report['budget_range'],
                'memo'              => $report['memo'] ?? null,
                'no_interval'       => $report['no_interval'],
                'last_updated_at' => $this->parseDateTime($report['last_updated_at'] ?? null),

                'last_updated_by'   => $report['last_updated_by'],
            ]);

            // 2️⃣ header bulan
            foreach ($report['header'] as $i => $label) {
                BudgetReportHeader::create([
                    'budget_report_id' => $budgetReport->id,
                    'seq'   => $i + 1,
                    'label' => $label,
                ]);
            }

            // 3️⃣ lines
            foreach ($report['data'] as $line) {
                $lineModel = BudgetReportLine::create([
                    'budget_report_id' => $budgetReport->id,
                    'name'             => $line['name'],
                    'line_type'        => $line['line_type'],
                    'has_sub_label'    => $line['has_sub_label'],
                ]);

                // total di level line
                $this->storeAmounts($lineModel, $line['total'], $report['header']);

                // children
                foreach ($line['children'] as $child) {
                    $childModel = BudgetReportLineChildren::create([
                        'budget_report_line_id' => $lineModel->id,
                        'label_name' => $child['label_name'] ?: null,
                    ]);

                    $this->storeAmounts($childModel, $child['total'], $report['header']);

                    // accounts
                    foreach ($child['account'] as $account) {
                        $accountModel = BudgetReportAccount::create([
                            'line_child_id'       => $childModel->id,
                            'external_account_id' => $account['id'],
                            'account_number'      => $account['number'],
                            'account_name'        => $account['name'],
                        ]);

                        $this->storeAmounts(
                            $accountModel,
                            $account['total'],
                            $report['header']
                        );
                    }
                }
            }

            return $budgetReport;
        });
    }

    private function storeAmounts($model, array $totals, array $headers): void
    {
        foreach ($totals as $index => $amount) {
            BudgetReportAmount::create([
                'amountable_type' => get_class($model),
                'amountable_id'   => $model->id,
                'period_index'    => $index + 1,
                'period_label'    => $headers[$index] ?? '',
                'amount'          => $amount,
            ]);
        }
    }

    private function toDate(string $date): string
    {
        return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
    }
}
