<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Tower extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;
    protected $fillable = ['name','active'];
    protected $casts = ['active'=>'boolean'];
    public function apartments(){return $this->hasMany(Apartment::class);}    
}
