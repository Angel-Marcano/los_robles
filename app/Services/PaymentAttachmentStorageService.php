<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentAttachmentStorageService
{
    public function activeDisk(): string
    {
        $preferred = (string) config('filesystems.payment_attachments.disk', 'public');
        if ($preferred === 's3' && !$this->canUseS3()) {
            return $this->fallbackDisk();
        }

        return $preferred;
    }

    public function storeForInvoice(UploadedFile $file, Invoice $invoice): string
    {
        $disk = $this->activeDisk();
        $directory = $this->invoiceAttachmentsDirectory($invoice);
        $fileName = $this->buildFileName($file);

        $path = Storage::disk($disk)->putFileAs($directory, $file, $fileName, [
            'visibility' => $this->visibilityForDisk($disk),
        ]);

        return (string) $path;
    }

    public function buildReviewLinks(array $paths): array
    {
        $links = [];
        foreach ($paths as $path) {
            $url = $this->resolveUrl((string) $path);
            if ($url === null) {
                continue;
            }

            $links[] = [
                'path' => (string) $path,
                'url' => $url,
            ];
        }

        return $links;
    }

    public function resolveUrl(string $path): ?string
    {
        $path = ltrim($path, '/');
        $disk = $this->resolveDiskForPath($path);
        if ($disk === null) {
            return null;
        }

        if ($disk === 's3') {
            return Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes($this->temporaryUrlTtlMinutes())
            );
        }

        return Storage::disk($disk)->url($path);
    }

    protected function resolveDiskForPath(string $path): ?string
    {
        $activeDisk = $this->activeDisk();
        if (Storage::disk($activeDisk)->exists($path)) {
            return $activeDisk;
        }

        $fallback = $this->fallbackDisk();
        if ($fallback !== $activeDisk && Storage::disk($fallback)->exists($path)) {
            return $fallback;
        }

        return null;
    }

    protected function canUseS3(): bool
    {
        if (!class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
            return false;
        }

        $s3 = (array) config('filesystems.disks.s3', []);
        $required = ['key', 'secret', 'region', 'bucket'];
        foreach ($required as $field) {
            if (empty($s3[$field])) {
                return false;
            }
        }

        return true;
    }

    protected function tenantSlug(): string
    {
        $condo = app()->bound('currentCondominium') ? app('currentCondominium') : null;
        $source = null;

        if ($condo) {
            $source = $condo->subdomain ?: $condo->name;
        }

        if (empty($source)) {
            $source = config('app.name', 'cliente');
        }

        $slug = Str::slug((string) $source);

        return $slug !== '' ? $slug : 'cliente';
    }

    protected function invoiceAttachmentsDirectory(Invoice $invoice): string
    {
        return $this->tenantSlug().'/facturas/factura_'.$invoice->id.'/comprobantes';
    }

    protected function buildFileName(UploadedFile $file): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = Str::slug((string) $base);
        if ($base === '') {
            $base = 'archivo';
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = 'bin';
        }

        return now()->format('YmdHis').'_'.Str::random(8).'_'.$base.'.'.$ext;
    }

    protected function visibilityForDisk(string $disk): string
    {
        return $disk === 's3' ? 'private' : 'public';
    }

    protected function fallbackDisk(): string
    {
        return (string) config('filesystems.payment_attachments.fallback_disk', 'public');
    }

    protected function temporaryUrlTtlMinutes(): int
    {
        return (int) config('filesystems.payment_attachments.s3_temporary_url_ttl_minutes', 20);
    }
}