<?php
namespace App\Observers; use App\Models\Invoice; use App\Services\AuditService;
class InvoiceObserver { public function created(Invoice $invoice){ app(AuditService::class)->log('invoice_created','Invoice',$invoice->id,$invoice->toArray()); } public function updated(Invoice $invoice){ app(AuditService::class)->log('invoice_updated','Invoice',$invoice->id,$invoice->getChanges()); } }
