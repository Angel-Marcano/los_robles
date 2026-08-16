<?php

namespace Tests\Unit\Chatbot;

use App\Services\Chatbot\DeepSeekClient;
use App\Services\Chatbot\IntentClassifier;
use PHPUnit\Framework\TestCase;

class IntentClassifierTest extends TestCase
{
    public function test_rule_based_balance_intent(): void
    {
        $client = $this->createMock(DeepSeekClient::class);
        $client->method('chat')->willReturn(['content' => '{}']);

        $classifier = new IntentClassifier($client);
        $result = $classifier->classify('¿Cuál es mi saldo?');

        $this->assertSame('balance', $result['intent']);
        $this->assertSame('high', $result['confidence']);
    }

    public function test_rule_based_report_payment_intent(): void
    {
        $client = $this->createMock(DeepSeekClient::class);
        $client->method('chat')->willReturn(['content' => '{}']);

        $classifier = new IntentClassifier($client);
        $result = $classifier->classify('Quiero reportar un pago de 100 USD');

        $this->assertSame('report_payment', $result['intent']);
    }

    public function test_extracts_amount_usd(): void
    {
        $client = $this->createMock(DeepSeekClient::class);
        $client->method('chat')->willReturn(['content' => '{}']);

        $classifier = new IntentClassifier($client);
        $result = $classifier->classify('Reportar pago de 250,50 USD para factura 12');

        $this->assertSame(250.5, $result['entities']['amount_usd']);
        $this->assertSame(12, $result['entities']['invoice_id']);
    }
}
