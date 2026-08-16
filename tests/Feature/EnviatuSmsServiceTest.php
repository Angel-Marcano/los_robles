<?php

namespace Tests\Feature;

use App\Services\EnviatuSmsService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EnviatuSmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.enviatusms.api_key' => 'test-api-key',
            'services.enviatusms.base_url' => 'https://www.enviatusms.com/api',
            'services.enviatusms.endpoint_balance' => 'balance',
            'services.enviatusms.endpoint_send' => 'sms/send',
            'services.enviatusms.timeout' => 10,
            'services.enviatusms.retry_times' => 1,
            'services.enviatusms.retry_sleep_ms' => 50,
        ]);
    }

    public function test_get_balance_sends_get_with_api_key_in_query(): void
    {
        Http::fake([
            'https://www.enviatusms.com/api/balance*' => Http::response([
                'success' => true,
                'balance' => 55.45,
            ], 200),
        ]);

        $service = app(EnviatuSmsService::class);
        $response = $service->getBalance();

        $this->assertTrue((bool) ($response['success'] ?? false));
        $this->assertEquals(55.45, (float) ($response['balance'] ?? 0));

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://www.enviatusms.com/api/balance')
                && str_contains($request->url(), 'api_key=test-api-key');
        });
    }

    public function test_send_sms_sends_post_json_with_api_key_in_query(): void
    {
        Http::fake([
            'https://www.enviatusms.com/api/sms/send*' => Http::response([
                'success' => true,
                'campaign_id' => 'CMP-123',
            ], 200),
        ]);

        $service = app(EnviatuSmsService::class);
        $payload = [
            'to' => '584123456789',
            'message' => 'Hola desde test',
        ];

        $response = $service->sendSms($payload);

        $this->assertTrue((bool) ($response['success'] ?? false));
        $this->assertEquals('CMP-123', $response['campaign_id'] ?? null);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://www.enviatusms.com/api/sms/send')
                && str_contains($request->url(), 'api_key=test-api-key')
                && ($body['to'] ?? null) === '584123456789'
                && ($body['message'] ?? null) === 'Hola desde test';
        });
    }

    public function test_request_throws_exception_when_api_key_is_missing(): void
    {
        config(['services.enviatusms.api_key' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ENVIATUSMS_API_KEY is not configured.');

        $service = app(EnviatuSmsService::class);
        $service->getBalance();
    }
}
