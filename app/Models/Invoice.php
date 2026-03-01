<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Invoice extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Traits\UsesTenantConnection;
    protected $fillable=['number','parent_id','apartment_id','tower_id','created_by','period','due_date','status','late_fee_type','late_fee_scope','late_fee_value','late_fee_accrued_usd','late_fee_accrued_ves','exchange_rate_used','total_usd','total_ves','paid_at','paid_exchange_rate','owner_name','owner_email','owner_document'];
    protected $casts=['paid_at'=>'datetime','due_date'=>'date','total_usd'=>'decimal:2','total_ves'=>'decimal:2','late_fee_value'=>'decimal:2','late_fee_accrued_usd'=>'decimal:2','late_fee_accrued_ves'=>'decimal:2','paid_exchange_rate'=>'decimal:6'];

    public function statusLabel(): string
    {
        switch ($this->status) {
            case 'draft':
                return 'Borrador';
            case 'pending':
                return 'Pendiente';
            case 'paid':
                return 'Pagada';
            default:
                return strtoupper((string) $this->status);
        }
    }

    public function lateFeeScopeLabel(): string
    {
        switch ($this->late_fee_scope) {
            case 'day':
                return 'Diaria';
            case 'week':
                return 'Semanal';
            case 'month':
                return 'Mensual';
            default:
                return strtoupper((string) $this->late_fee_scope);
        }
    }

    public function lateFeeTypeLabel(): string
    {
        switch ($this->late_fee_type) {
            case 'percent':
                return 'Porcentaje';
            case 'fixed':
                return 'Fijo';
            default:
                return strtoupper((string) $this->late_fee_type);
        }
    }

    public function lateFeeLabel(): string
    {
        if (!$this->late_fee_type || !$this->late_fee_scope || !$this->late_fee_value) {
            return 'Sin mora';
        }
        $value = (float) $this->late_fee_value;
        if ($this->late_fee_type === 'percent') {
            return 'Mora ' . $this->lateFeeScopeLabel() . ' (' . number_format($value, 2) . '%)';
        }
        return 'Mora ' . $this->lateFeeScopeLabel() . ' (' . number_format($value, 2) . ' USD)';
    }
    public function computeLateFeeUsd(): float {
        if($this->status==='paid'){ return (float)$this->late_fee_accrued_usd; }
        if(!$this->late_fee_type || !$this->late_fee_scope || !$this->late_fee_value || !$this->due_date){ return 0.0; }
        $now=now(); if($now->lte($this->due_date)){ return 0.0; }
        $daysLate=$this->due_date->diffInDays($now);
        // match no disponible en PHP 7.4; usar switch
        switch($this->late_fee_scope){
            case 'day': $scopeDays=1; break;
            case 'week': $scopeDays=7; break;
            case 'month': $scopeDays=30; break;
            default: $scopeDays=1; break;
        }
        // Cobro por periodos COMPLETOS: week => cobra 1 a partir del día 7, 2 a partir del día 14, etc.
        $units = $scopeDays > 0 ? intdiv($daysLate, $scopeDays) : 0;
        if($units <= 0){ return 0.0; }
        if($this->late_fee_type==='percent'){ return round($this->total_usd * ($this->late_fee_value/100) * $units,2); }
        return round($this->late_fee_value * $units,2);
    }
    public function computeLateFeeVes(): float {
        if($this->status==='paid'){
            return (float) $this->late_fee_accrued_ves;
        }
        $feeUsd=$this->computeLateFeeUsd();
        return round($feeUsd * $this->exchange_rate_used,2);
    }
    public function items(){return $this->hasMany(InvoiceItem::class);}    
    public function tower(){return $this->belongsTo(Tower::class);}    
    public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function parent(){return $this->belongsTo(Invoice::class,'parent_id');}
    public function children(){return $this->hasMany(Invoice::class,'parent_id');}
    public function apartment(){return $this->belongsTo(Apartment::class);}    

    public function paymentReports(){return $this->hasMany(PaymentReport::class);} 

    public function dueUsdEquivalent(): float
    {
        return (float) $this->total_usd + (float) $this->computeLateFeeUsd();
    }

    public function approvedPaidUsdEquivalent(): float
    {
        return $this->sumPaymentsUsdEquivalentByStatus('approved');
    }

    public function reportedPaidUsdEquivalent(): float
    {
        return $this->sumPaymentsUsdEquivalentByStatus('reported');
    }

    public function remainingUsdEquivalent(bool $includeReported = false): float
    {
        $due = (float) $this->dueUsdEquivalent();
        $paid = (float) $this->approvedPaidUsdEquivalent();
        if ($includeReported) {
            $paid += (float) $this->reportedPaidUsdEquivalent();
        }
        return max(0.0, round($due - $paid, 2));
    }

    public function hasReportedPayments(): bool
    {
        return $this->paymentReports()->where('status', 'reported')->exists();
    }

    protected function sumPaymentsUsdEquivalentByStatus(string $status): float
    {
        $reports = $this->paymentReports()
            ->where('status', $status)
            ->get(['amount_usd', 'amount_ves', 'exchange_rate_used', 'usd_equivalent']);

        $sum = 0.0;
        foreach ($reports as $pr) {
            $sum += (float) $pr->usdEquivalent();
        }
        return (float) $sum;
    }
}
