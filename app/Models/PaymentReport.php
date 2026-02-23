<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReport extends Model
{
	use HasFactory, \App\Models\Traits\UsesTenantConnection;

	protected $fillable = ['invoice_id','user_id','amount_usd','amount_ves','exchange_rate_used','usd_equivalent','status','files','notes'];
	protected $casts = ['amount_usd' => 'decimal:2','amount_ves' => 'decimal:2','usd_equivalent' => 'decimal:2','files' => 'array'];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
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
