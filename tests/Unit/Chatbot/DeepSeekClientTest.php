<?php

namespace Tests\Unit\Chatbot;

use App\Services\Chatbot\DeepSeekClient;
use PHPUnit\Framework\TestCase;

class DeepSeekClientTest extends TestCase
{
    public function test_throws_when_api_key_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DeepSeekClient([
            'api_key' => null,
            'base_url' => 'https://api.deepseek.com',
        ]);
    }
}
