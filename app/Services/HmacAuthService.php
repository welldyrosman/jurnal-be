<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HmacAuthService
{
    private $hmac_username = 'MOkaZr3AulXIoMsV';
    private $hmac_secret = 'TU9yva0PVF621DU3BoGnGcn2mf0gJK4d';

    /**
     * Generate HMAC signature
     */
    public function generateSignature($method, $path, $dateString)
    {
        $requestLine = $method . ' ' .  $path . ' HTTP/1.1';
        $signatureData = 'date: ' . $dateString . "\n" . $requestLine;

        $digest = hash_hmac('sha256', $signatureData, $this->hmac_secret, true);
        $signature = base64_encode($digest);

        return $signature;
    }

    /**
     * Build HMAC Authorization header
     */
    public function buildAuthorizationHeader($method, $path)
    {
        $dateString = $this->getUTCDate();
        $signature = $this->generateSignature($method, $path, $dateString);

        $hmac_header = 'hmac username="' . $this->hmac_username .
            '", algorithm="hmac-sha256", headers="date request-line", signature="' .
            $signature . '"';

        return [
            'Authorization' => $hmac_header,
            'Date' => $dateString
        ];
    }

    /**
     * Get UTC date string in RFC format
     */
    private function getUTCDate()
    {
        return gmdate('D, d M Y H:i:s T');
    }

    /**
     * Make authenticated request to Qontak API
     */
    public function makeRequest($method, $endpoint, $data = [], $headers = [])
    {
        $fullUrl = 'https://api.mekari.com/qontak/crm/' . $endpoint;

        // Parse URL to get path
        $urlParts = parse_url($fullUrl);
        $path = $urlParts['path'];
        if (isset($urlParts['query'])) {
            $path .= '?' . $urlParts['query'];
        }

        // Generate HMAC headers
        $hmacHeaders = $this->buildAuthorizationHeader($method, $path);

        // Merge headers
        $finalHeaders = array_merge($hmacHeaders, [
            'X-Crm-User-Sso-Id' => '7f3d64a4-6f4f-4b1a-bd7e-5c120028da64',
            'Content-Type' => 'application/json',
        ], $headers);

        // Make request
        $response = Http::withHeaders($finalHeaders)
            ->{strtolower($method)}($fullUrl, $data);

        return $response;
    }

    /**
     * Shortcut untuk GET request
     */
    public function get($endpoint, $headers = [])
    {
        return $this->makeRequest('GET', $endpoint, [], $headers);
    }

    /**
     * Shortcut untuk POST request
     */
    public function post($endpoint, $data = [], $headers = [])
    {
        return $this->makeRequest('POST', $endpoint, $data, $headers);
    }

    /**
     * Shortcut untuk PUT request
     */
    public function put($endpoint, $data = [], $headers = [])
    {
        return $this->makeRequest('PUT', $endpoint, $data, $headers);
    }

    /**
     * Shortcut untuk DELETE request
     */
    public function delete($endpoint, $headers = [])
    {
        return $this->makeRequest('DELETE', $endpoint, [], $headers);
    }
}
