<?php
namespace App\Models; use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
	use HasFactory, \App\Models\Traits\UsesTenantConnection; 
	protected $fillable=['user_id','action','entity_type','entity_id','changes','ip']; 
	protected $casts=['changes'=>'array']; 
	public function user(){ return $this->belongsTo(User::class); }
}
