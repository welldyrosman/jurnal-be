<?php

namespace App\Services\Jurnal;

class LabaRugiService
{
    public function getAccountOnly(array $data): array
    {
        $filteredAccounts = [];
        $accounts = $data['accounts'] ?? [];
        foreach ($accounts as $item) {
            $filteredAccounts[] = [
                'account_name' => $item['account_name'] ?? '',
                'beginning_balance'      => $item['beginning_balance'] ?? 0,
                'debit'    => $item['debit'] ?? 0,
                'credit'    => $item['credit'] ?? 0,
                'ending_balance'      => $item['ending_balance'] ?? 0,
            ];
        }
        return array_values($filteredAccounts);
    }
}
