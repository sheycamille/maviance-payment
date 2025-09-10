<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EnkapService
{
    protected string $baseUrl;
    protected string $token;
    protected string $authUrl;
    protected string $consumerKey;
    protected string $consumerSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.enkap.base_url');
        $this->token   = config('services.enkap.access_token');
        // $this->authUrl = config('services.enkap.auth_url');
        $this->consumerKey = config('services.enkap.consumer_key');
        $this->consumerSecret = config('services.enkap.consumer_secret');
    }

    /** Get Access Token (cached for ~55 minutes) */
    protected function getAccessToken(): string
    {
        return Cache::remember('enkap_access_token', 3300, function () {
            $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

            $response = Http::asForm()->withHeaders([
                'Authorization' => "Basic {$credentials}",
            ])->post($this->authUrl, [
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to generate access token: " . $response->body());
            }

            return $response->json()['access_token'];
        });
    }

    protected function headers()
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    /** Place a payment order */
    public function createOrder(array $data)
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/api/order", $data);

        return $response->json();
    }

    /** Query payment status */
    public function getStatus(array $query)
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/api/order/status", $query);

        return $response->json();
    }

    /** Get payment details */
    public function getDetails(array $query)
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/api/order", $query);

        return $response->json();
    }

    /** Setup callback URLs */
    public function setupCallbackUrls()
    {
        $payload = [
            'returnUrl'      => config('services.enkap.return_url'),
            'notificationUrl'=> config('services.enkap.notify_url'),
        ];

        $response = Http::withHeaders($this->headers())
            ->put("{$this->baseUrl}/api/order/setup", $payload);

        return $response->json();
    }
}
