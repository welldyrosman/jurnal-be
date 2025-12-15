<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class MekariApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->clientId     = env('MEKARI_API_CLIENT_ID');
        $this->clientSecret = env('MEKARI_API_CLIENT_SECRET');
        $this->baseUrl      = env('MEKARI_API_BASE_URL');
        // dd($this->clientSecret);
        $this->client = new Client([
            'base_uri' => $this->baseUrl
        ]);
    }

    /**
     * Generate HMAC Authentication headers
     */
    private function generateHeaders($method, $pathWithQuery)
    {
        $datetime     = Carbon::now()->toRfc7231String(); // same format as example
        $requestLine  = "{$method} {$pathWithQuery} HTTP/1.1";
        $payload      = implode("\n", ["date: {$datetime}", $requestLine]);

        $digest    = hash_hmac('sha256', $payload, $this->clientSecret, true);
        $signature = base64_encode($digest);

        return [
            'Content-Type'  => 'application/json',
            'Date'          => $datetime,
            'Authorization' => "hmac username=\"{$this->clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\""
        ];
    }

    /**
     * Execute request to Mekari
     */
    public function request($method, $path, $query = '', $body = [], $extraHeaders = [])
    {
        $pathWithQuery = $path . $query;
        $headers = array_merge(
            $this->generateHeaders($method, $pathWithQuery),
            $extraHeaders
        );
        //  dd($pathWithQuery, $headers, $this->client);
        try {
            $response = $this->client->request($method, $pathWithQuery, [
                'headers' => $headers,
                'body'    => json_encode($body)
            ]);

            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            return [
                'error'      => true,
                'request'    => \GuzzleHttp\Psr7\Message::toString($e->getRequest()),
                'response'   => \GuzzleHttp\Psr7\Message::toString($e->getResponse())
            ];
        }
    }
}
