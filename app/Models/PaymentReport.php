<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentReport extends Model
{
	use HasFactory, SoftDeletes, \App\Models\Traits\UsesTenantConnection;

	protected $fillable = ['invoice_id','user_id','apartment_id','reported_by','payment_method','reference_number','paid_at','amount_usd','amount_ves','exchange_rate_used','exchange_rate_valid_from','currency_rate_id','usd_equivalent','status','files','notes'];
	protected $casts = ['amount_usd' => 'decimal:2','amount_ves' => 'decimal:2','usd_equivalent' => 'decimal:2','exchange_rate_valid_from' => 'datetime','files' => 'array'];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function currencyRate()
	{
		return $this->belongsTo(CurrencyRate::class);
	}

	public function comments()
	{
		return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'asc');
	}

	public function statusLabel(): string
	{
		switch ($this->status) {
			case 'reported':
				return 'Reportado';
			case 'approved':
				return 'Aprobado';
			case 'rejected':
				return 'Rechazado';
			default:
				return strtoupper((string) $this->status);
		}
	}

	public function usdEquivalent(): float
	{
		if ($this->usd_equivalent !== null) {
			return (float) $this->usd_equivalent;
		}
		$usd = (float) ($this->amount_usd ?? 0);
		$ves = (float) ($this->amount_ves ?? 0);
		$rate = (float) ($this->exchange_rate_used ?? 0);
		$vesInUsd = ($rate > 0) ? ($ves / $rate) : 0.0;
		return $usd + $vesInUsd;
	}
}
