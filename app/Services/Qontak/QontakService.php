<?php

namespace App\Services\Qontak;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QontakService
{
    private string $baseUrl = 'https://app.qontak.com/api/v3.1';

    /**
     * Base HTTP client untuk Qontak
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken(config('services.qontak.token'))
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 200);
    }

    /**
     * Generic GET request
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $response = $this->client()->get($endpoint, $params);

        if ($response->failed()) {
            logger()->error('Qontak API Error', [
                'endpoint' => $endpoint,
                'params'   => $params,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            throw new \RuntimeException('Qontak API request failed');
        }

        return $response->json();
    }

    // =========================
    // Public API Methods
    // =========================

    public function getCompanies(int $page = 1): array
    {
        return $this->get('/companies', ['page' => $page]);
    }

    public function getContacts(int $page = 1): array
    {
        return $this->get('/contacts', ['page' => $page]);
    }

    public function getProducts(int $page = 1): array
    {
        return $this->get('/products', ['page' => $page]);
    }

    public function getDeals(int $page = 1): array
    {
        return $this->get('/deals', ['page' => $page]);
    }
    public function getProductAssociation(int $page = 1): array
    {
        return $this->get('/products_association', ['page' => $page]);
    }
}
