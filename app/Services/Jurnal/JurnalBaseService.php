<?php

namespace App\Services\Jurnal;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Exception;

/**
 * Base service for interacting with the Jurnal.id Partner API.
 */
abstract class JurnalBaseService
{
    protected PendingRequest $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.jurnal.api_key');
        if (empty($this->apiKey)) {
            throw new Exception('Jurnal API Key is not set in config/services.php or .env file.');
        }

        $this->client = Http::baseUrl(config('services.jurnal.base_url', 'https://api.jurnal.id/partner/core/api/v1/'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->timeout(60) 
            ->retry(3, 100); 
    }
    protected function get(string $endpoint, array $query = []): array
    {
        $queryWithToken = array_merge(['access_token' => $this->apiKey], $query);
        $response = $this->client->get($endpoint, $queryWithToken);
        $response->throw();
        return $response->json();
    }
}

