<?php
namespace App\Observers; use App\Models\PaymentReport; use App\Services\AuditService;
class PaymentReportObserver { public function created(PaymentReport $pr){ app(AuditService::class)->log('payment_report_created_observer','PaymentReport',$pr->id,$pr->toArray()); } public function updated(PaymentReport $pr){ app(AuditService::class)->log('payment_report_updated','PaymentReport',$pr->id,$pr->getChanges()); } }
