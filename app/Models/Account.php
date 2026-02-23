<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Account extends Model {
	use HasFactory, \App\Models\Traits\UsesTenantConnection;
	protected $fillable=['owner_type','owner_id','name','balance_usd','balance_ves','condominium_id'];
	protected $casts=['balance_usd'=>'decimal:2','balance_ves'=>'decimal:2'];
	public function movements(){return $this->hasMany(AccountMovement::class);} 
	public function condominium(){return $this->belongsTo(Condominium::class);} 
}
