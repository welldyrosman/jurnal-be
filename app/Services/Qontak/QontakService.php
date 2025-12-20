<?php

namespace App\Services\Qontak;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QontakService
{
    private string $baseUrl;
    private string $hmacUsername;
    private string $hmacSecret;
    private string $ssoUserId;

    public function __construct()
    {
        // Set base to the domain only to avoid path duplication in HMAC signatures
        $this->baseUrl = config('app.mekari_base_url', 'https://api.mekari.com');
        $this->hmacUsername = config('app.hmac_username');
        $this->hmacSecret = config('app.hmac_secret');
        $this->ssoUserId = config('app.sso_user_id');
    }

    /**
     * Generate HMAC signature
     */
    private function generateSignature(string $method, string $path, string $dateString): string
    {
        // The request line must match exactly what the server sees
        $requestLine = strtoupper($method) . ' ' . $path . ' HTTP/1.1';
        $signatureData = 'date: ' . $dateString . "\n" . $requestLine;

        $digest = hash_hmac('sha256', $signatureData, $this->hmacSecret, true);
        return base64_encode($digest);
    }

    /**
     * Build HMAC Authorization headers
     */
    private function buildAuthorizationHeaders(string $method, string $path): array
    {
        $dateString = $this->getUTCDate();
        $signature = $this->generateSignature($method, $path, $dateString);

        $hmacHeader = 'hmac username="' . $this->hmacUsername .
            '", algorithm="hmac-sha256", headers="date request-line", signature="' .
            $signature . '"';

        return [
            'Authorization' => $hmacHeader,
            'Date' => $dateString,
            'X-Crm-User-Sso-Id' => $this->ssoUserId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get UTC date string in RFC format
     */
    private function getUTCDate(): string
    {
        // Fixed: removed the extra space in H:i:s
        return gmdate('D, d M Y H:i:s \G\M\T');
    }

    /**
     * Base HTTP client for Qontak with HMAC authentication
     */
    protected function client(string $method, string $endpoint, array $params = []): PendingRequest
    {
        $method = strtoupper($method);

        // Ensure endpoint starts with /
        $endpoint = '/' . ltrim($endpoint, '/');

        // Reconstruct the path with query parameters for the signature
        $path = $endpoint;
        if ($method === 'GET' && !empty($params)) {
            $path .= '?' . http_build_query($params);
        }

        $headers = $this->buildAuthorizationHeaders($method, $path);

        return Http::baseUrl($this->baseUrl)
            ->withHeaders($headers)
            ->timeout(30)
            ->retry(3, 200);
    }

    /**
     * Generic GET request
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $response = $this->client('GET', $endpoint, $params)->get($endpoint, $params);

        if ($response->failed()) {
            $this->logError('GET', $endpoint, $response, $params);
            throw new \RuntimeException('Qontak API GET request failed: ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Generic POST request
     */
    protected function post(string $endpoint, array $data = []): array
    {
        $response = $this->client('POST', $endpoint)->post($endpoint, $data);

        if ($response->failed()) {
            $this->logError('POST', $endpoint, $response, $data);
            throw new \RuntimeException('Qontak API POST request failed: ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Generic PUT request
     */
    protected function put(string $endpoint, array $data = []): array
    {
        $response = $this->client('PUT', $endpoint)->put($endpoint, $data);

        if ($response->failed()) {
            $this->logError('PUT', $endpoint, $response, $data);
            throw new \RuntimeException('Qontak API PUT request failed: ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Generic DELETE request
     */
    protected function delete(string $endpoint): array
    {
        $response = $this->client('DELETE', $endpoint)->delete($endpoint);

        if ($response->failed()) {
            $this->logError('DELETE', $endpoint, $response);
            throw new \RuntimeException('Qontak API DELETE request failed: ' . $response->status());
        }

        return $response->json();
    }

    private function logError(string $method, string $endpoint, $response, array $payload = []): void
    {
        logger()->error('Qontak API Error', [
            'method'   => $method,
            'endpoint' => $endpoint,
            'payload'  => $payload,
            'status'   => $response->status(),
            'body'     => $response->body(),
        ]);
    }

    // =========================
    // Public API Methods
    // =========================

    public function getCompanies(int $page = 1): array
    {
        return $this->get('/qontak/crm/companies', ['page' => $page]);
    }

    public function getContacts(int $page = 1): array
    {
        return $this->get('/qontak/crm/contacts', ['page' => $page]);
    }

    public function getProducts(int $page = 1): array
    {
        return $this->get('/qontak/crm/products', ['page' => $page]);
    }

    public function getDeals(int $page = 1): array
    {
        return $this->get('/qontak/crm/deals', ['page' => $page]);
    }

    public function getPipelines(int $page = 1): array
    {
        return $this->get('/qontak/crm/pipelines', ['page' => $page]);
    }

    public function getCompany(string $id): array
    {
        return $this->get('/qontak/crm/companies/' . $id);
    }

    public function createCompany(array $data): array
    {
        return $this->post('/qontak/crm/companies', $data);
    }

    public function updateCompany(string $id, array $data): array
    {
        return $this->put('/qontak/crm/companies/' . $id, $data);
    }

    public function deleteCompany(string $id): array
    {
        return $this->delete('/qontak/crm/companies/' . $id);
    }

    public function createContact(array $data): array
    {
        return $this->post('/qontak/crm/contacts', $data);
    }

    public function createDeal(array $data): array
    {
        return $this->post('/qontak/crm/deals', $data);
    }
    public function getProductAssociation(array $data): array
    {
        return $this->get('/qontak/crm/associations', $data);
    }
}
