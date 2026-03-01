<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Apartment extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;
    protected $fillable = ['tower_id','code','active','aliquot_percent'];
    protected $casts = ['active'=>'boolean','aliquot_percent'=>'decimal:8'];
    public function tower(){return $this->belongsTo(Tower::class);}    
    public function ownerships(){return $this->hasMany(Ownership::class);}    
}

