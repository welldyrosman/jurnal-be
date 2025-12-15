<?php

namespace App\Exports;

use App\Models\JurnalAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BudgetsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    /**
     * Ambil data untuk eksport
     */
    public function collection()
    {
        return JurnalAccount::orderBy('number', 'asc')
            ->with([
                'budgets' => fn ($query) => $query->where('year', $this->year),
                'accountGrouping',
                'budgetGrouping'
            ])
            ->get();
    }

    /**
     * Heading Excel
     */
    public function headings(): array
    {
        return [
            'account_code',
            'account_name',
            'account_group',
            'budget_group',
            'jan', 'feb', 'mar', 'apr', 'may', 'jun',
            'jul', 'aug', 'sep', 'oct', 'nov', 'des'
        ];
    }

    /**
     * Mapping tiap baris ke Excel
     */
    public function map($account): array
    {
        $b = $account->budgets->first(); // bisa null

        return [
            "'" . (string) $account->number,
            $account->name,
            $account->accountGrouping->name ?? '',
            $account->budgetGrouping->name ?? '',

            (int)($b?->budget_jan ?? 0),
            (int)($b?->budget_feb ?? 0),
            (int)($b?->budget_mar ?? 0),
            (int)($b?->budget_apr ?? 0),
            (int)($b?->budget_mei ?? 0),
            (int)($b?->budget_jun ?? 0),
            (int)($b?->budget_jul ?? 0),
            (int)($b?->budget_ags ?? 0),
            (int)($b?->budget_sep ?? 0),
            (int)($b?->budget_okt ?? 0),
            (int)($b?->budget_nov ?? 0),
            (int)($b?->budget_des ?? 0),
        ];
    }
}
