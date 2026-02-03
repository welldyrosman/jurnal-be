<?php

namespace App\Services\Jurnal;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class JurnalProfitLossBudgetService
{
    protected string $baseUrl;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl = 'https://api.jurnal.id/partner/core/api/v2';
        $this->accessToken = config('services.jurnal.api_key');
    }

    /**
     * Get Profit & Loss Budgeting Report
     */
    public function fetch($end_period)
    {
        $response = Http::timeout(60)
            ->retry(3, 1000)
            ->get("{$this->baseUrl}/profit_loss_budgeting", array_merge([
                'access_token' => $this->accessToken,
            ], [
                "interval" => 1,
                "no_interval" => 1,
                "budget_id" => 16224,
                "end_period" => $end_period,
                "profit_loss_id" => "default"
            ]));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }
}
