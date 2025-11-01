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
                // Header 'apikey' dihapus, otentikasi sekarang via query param 'access_token'
                'Accept' => 'application/json',
            ])
            ->timeout(60) // Increase timeout for API calls
            ->retry(3, 100); // Retry 3 times with 100ms delay if it fails
    }

    /**
     * Perform a GET request to the Jurnal API using access_token as a query parameter.
     *
     * @param string $endpoint The API endpoint (e.g., 'sales_invoices').
     * @param array  $query    The query parameters.
     * @return array The JSON decoded response.
     */
    protected function get(string $endpoint, array $query = []): array
    {
        // Menambahkan 'access_token' ke setiap parameter query pada request GET
        $queryWithToken = array_merge(['access_token' => $this->apiKey], $query);

        $response = $this->client->get($endpoint, $queryWithToken);

        // Throw an exception if the request was not successful
        $response->throw();

        return $response->json();
    }
}

