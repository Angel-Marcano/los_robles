<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReserveFundMovement extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = [
        'reserve_fund_id','direction','source','reserve_type','invoice_id','apartment_id',
        'amount_usd','amount_ves','exchange_rate','notes','user_id',
    ];
    protected $casts = [
        'amount_usd'=>'decimal:2','amount_ves'=>'decimal:2','exchange_rate'=>'decimal:6',
    ];

    public function reserveFund(){ return $this->belongsTo(ReserveFund::class); }
    public function invoice(){ return $this->belongsTo(Invoice::class); }
    public function apartment(){ return $this->belongsTo(Apartment::class); }
    public function user(){ return $this->belongsTo(User::class); }

    public function directionLabel(): string
    {
        return $this->direction === 'income' ? 'Ingreso' : 'Egreso';
    }

    public function sourceLabel(): string
    {
        switch ($this->source) {
            case 'invoice': return 'Factura';
            case 'manual': return 'Manual';
            case 'adjustment': return 'Ajuste';
            default: return strtoupper((string) $this->source);
        }
    }
}
