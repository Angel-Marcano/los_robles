<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Tower extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;
    protected $fillable = ['name','active','reserve_percent'];
    protected $casts = ['active'=>'boolean','reserve_percent'=>'decimal:2'];
    public function apartments(){return $this->hasMany(Apartment::class);}    
    public function reserveFund(){return $this->hasOne(ReserveFund::class);}    
}
