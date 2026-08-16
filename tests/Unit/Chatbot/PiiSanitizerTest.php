<?php

namespace Tests\Unit\Chatbot;

use App\Services\Chatbot\PiiSanitizer;
use PHPUnit\Framework\TestCase;

class PiiSanitizerTest extends TestCase
{
    public function test_sanitizes_email(): void
    {
        $input = 'Mi correo es juan@example.com';
        $this->assertStringContainsString('[EMAIL]', PiiSanitizer::sanitize($input));
        $this->assertStringNotContainsString('juan@example.com', PiiSanitizer::sanitize($input));
    }

    public function test_sanitizes_phone(): void
    {
        $input = 'Llámame al 0412-1234567';
        $this->assertStringContainsString('[TELEFONO]', PiiSanitizer::sanitize($input));
    }

    public function test_sanitizes_cedula(): void
    {
        $input = 'Mi cédula es V-12345678';
        $this->assertStringContainsString('[CEDULA]', PiiSanitizer::sanitize($input));
    }
}
