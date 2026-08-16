<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReserveFund extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = ['tower_id','condominium_id','name','balance_usd','balance_ves'];
    protected $casts = ['balance_usd'=>'decimal:2','balance_ves'=>'decimal:2'];

    public function tower(){ return $this->belongsTo(Tower::class); }
    public function condominium(){ return $this->belongsTo(\App\Models\Condominium::class); }
    public function movements(){ return $this->hasMany(ReserveFundMovement::class); }

    /**
     * Obtiene (o crea) el fondo de reserva de una torre. Cada torre tiene su fondo aislado.
     */
    public static function forTower(Tower $tower): self
    {
        return static::firstOrCreate(
            ['tower_id' => $tower->id],
            ['name' => 'Fondo de Reserva '.$tower->name, 'balance_usd' => 0, 'balance_ves' => 0]
        );
    }

    /**
     * Obtiene (o crea) el fondo de reserva general del condominio (tower_id = null).
     */
    public static function forCondominium(\App\Models\Condominium $condominium): self
    {
        return static::firstOrCreate(
            ['tower_id' => null, 'condominium_id' => $condominium->id],
            ['name' => 'Fondo de Reserva General', 'balance_usd' => 0, 'balance_ves' => 0]
        );
    }

    /**
     * ¿Es el fondo general del condominio (sin torre)?
     */
    public function isGeneral(): bool
    {
        return $this->tower_id === null;
    }
}
