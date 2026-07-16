<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EnviatuSmsService
{
    /**
     * Performs a request to EnviaTuSMS adding api_key as query parameter.
     */
    public function request(string $endpoint, array $query = [], array $json = [], string $method = 'GET'): array
    {
        $apiKey = (string) config('services.enviatusms.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('ENVIATUSMS_API_KEY is not configured.');
        }

        $baseUrl = rtrim((string) config('services.enviatusms.base_url', 'https://www.enviatusms.com/api'), '/');
        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $query = array_merge($query, ['api_key' => $apiKey]);

        $timeout = max(1, (int) config('services.enviatusms.timeout', 10));
        $retryTimes = max(0, (int) config('services.enviatusms.retry_times', 1));
        $retrySleepMs = max(0, (int) config('services.enviatusms.retry_sleep_ms', 200));

        $client = Http::acceptJson()->timeout($timeout);
        if ($retryTimes > 0) {
            $client = $client->retry($retryTimes, $retrySleepMs, throw: false);
        }

        $method = strtoupper($method);
        $options = ['query' => $query];

        if ($method !== 'GET') {
            $options['json'] = $json;
        }

        $response = $client->send($method, $url, $options);
        $response->throw();

        $decoded = $response->json();
        return is_array($decoded) ? $decoded : ['raw' => $response->body()];
    }

    public function getBalance(): array
    {
        $endpoint = (string) config('services.enviatusms.endpoint_balance', 'balance');
        return $this->request($endpoint);
    }

    public function sendSms(array $payload): array
    {
        $endpoint = (string) config('services.enviatusms.endpoint_send', 'sms/send');
        return $this->request($endpoint, [], $payload, 'POST');
    }
}
