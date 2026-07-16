<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AuditService;

class InvoiceObserver
{
	/**
	 * Campos que invalidan la firma al cambiar luego de firmada.
	 * Incluye estado y datos clave de monto/propietario.
	 */
	protected $signatureCriticalFields = [
		'status',
		'total_usd',
		'total_ves',
		'exchange_rate_used',
		'due_date',
		'late_fee_type',
		'late_fee_scope',
		'late_fee_value',
		'owner_name',
		'owner_email',
		'owner_document',
	];

	public function creating(Invoice $invoice): void
	{
		$this->invalidateSignatureIfNeeded($invoice);
	}

	public function updating(Invoice $invoice): void
	{
		$this->invalidateSignatureIfNeeded($invoice);
	}

	public function created(Invoice $invoice): void
	{
		app(AuditService::class)->log('invoice_created', 'Invoice', $invoice->id, $invoice->toArray());
	}

	public function updated(Invoice $invoice): void
	{
		app(AuditService::class)->log('invoice_updated', 'Invoice', $invoice->id, $invoice->getChanges());
	}

	protected function invalidateSignatureIfNeeded(Invoice $invoice): void
	{
		if (!$invoice->exists) {
			return;
		}

		$alreadySigned = !empty($invoice->getOriginal('invoice_signature'));
		if (!$alreadySigned) {
			return;
		}

		foreach ($this->signatureCriticalFields as $field) {
			if ($invoice->isDirty($field)) {
				$invoice->invoice_signature = null;
				$invoice->signed_at = null;
				break;
			}
		}
	}
}
