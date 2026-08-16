<?php

namespace App\Services;

use App\Models\Invoice;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class InvoiceVerificationService
{
    public function ensureSignature(Invoice $invoice): void
    {
        if (!in_array((string) $invoice->status, ['pending', 'paid'], true)) {
            return;
        }

        if (!empty($invoice->invoice_signature) && !empty($invoice->signed_at)) {
            return;
        }

        $invoice->forceFill([
            'invoice_signature' => $this->computeInvoiceSignature($invoice),
            'signed_at' => now(),
        ])->saveQuietly();
    }

    public function computeInvoiceSignature(Invoice $invoice): string
    {
        $payload = implode('|', [
            (string) $invoice->id,
            (string) $this->tenantId(),
            (string) $invoice->status,
            (string) round((float) $invoice->total_usd, 2),
            (string) round((float) $invoice->total_ves, 2),
            (string) round((float) $invoice->exchange_rate_used, 6),
            (string) optional($invoice->due_date)->format('Y-m-d'),
            (string) $invoice->owner_name,
            (string) $invoice->owner_email,
            (string) $invoice->owner_document,
        ]);

        return hash_hmac('sha256', $payload, $this->hmacKey());
    }

    public function generateToken(Invoice $invoice): string
    {
        $this->ensureSignature($invoice);

        $payload = [
            'i' => (int) $invoice->id,
            't' => (int) $this->tenantId(),
            's' => (string) $invoice->invoice_signature,
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $mac = hash_hmac('sha256', $encodedPayload, $this->hmacKey());

        return 'v1.'.$encodedPayload.'.'.$mac;
    }

    public function verificationUrl(Invoice $invoice): string
    {
        return route('verify.invoice.short', ['token' => $this->generateToken($invoice)]);
    }

    public function qrSvgForInvoice(Invoice $invoice, int $size = 160): string
    {
        $url = $this->verificationUrl($invoice);
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    public function verifyToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== 'v1') {
            return ['status' => 'no-existe'];
        }

        $encodedPayload = $parts[1];
        $mac = $parts[2];
        $expectedMac = hash_hmac('sha256', $encodedPayload, $this->hmacKey());
        if (!hash_equals($expectedMac, $mac)) {
            return ['status' => 'no-existe'];
        }

        $decoded = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (!is_array($decoded) || empty($decoded['i']) || empty($decoded['t']) || empty($decoded['s'])) {
            return ['status' => 'no-existe'];
        }

        if ((int) $decoded['t'] !== (int) $this->tenantId()) {
            return ['status' => 'no-existe'];
        }

        $invoice = Invoice::withTrashed()->find((int) $decoded['i']);
        if (!$invoice) {
            return ['status' => 'no-existe'];
        }

        if ($invoice->trashed() || $invoice->isVoided()) {
            return ['status' => 'anulada', 'invoice' => $invoice];
        }

        if (empty($invoice->invoice_signature)) {
            return ['status' => 'anulada', 'invoice' => $invoice];
        }

        $expectedSignature = $this->computeInvoiceSignature($invoice);
        if (!hash_equals((string) $invoice->invoice_signature, $expectedSignature)) {
            return ['status' => 'anulada', 'invoice' => $invoice];
        }

        if (!hash_equals((string) $decoded['s'], (string) $invoice->invoice_signature)) {
            return ['status' => 'anulada', 'invoice' => $invoice];
        }

        return ['status' => 'valida', 'invoice' => $invoice];
    }

    protected function tenantId(): int
    {
        $condo = app()->bound('currentCondominium') ? app('currentCondominium') : null;

        return $condo ? (int) $condo->id : 0;
    }

    protected function hmacKey(): string
    {
        $appKey = (string) config('app.key', '');
        if (Str::startsWith($appKey, 'base64:')) {
            return base64_decode(substr($appKey, 7)) ?: $appKey;
        }

        return $appKey;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }
}
