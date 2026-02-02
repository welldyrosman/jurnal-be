<?php

namespace App\Services\Budget;

use Illuminate\Support\Facades\Http;

class JurnalBudgetFetcher
{
    public function fetch(
        string $accessToken,
        int $templateId,
        string $startPeriod,
        int $interval = 1,
        int $noInterval = 12
    ): array {
        $baseUrl = 'https://api.jurnal.id/partner/core/api/v2';
        $response = Http::get($baseUrl . '/budget_management', [
            'access_token' => $accessToken,
            'templateId'   => $templateId,
            'interval'     => $interval,
            'no_interval'  => $noInterval,
            'start_period' => $startPeriod,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed fetch budget from Jurnal');
        }

        return $response->json('budgeting_report');
    }
}
