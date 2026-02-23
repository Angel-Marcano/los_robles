<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Condominium extends Model
{
    use HasFactory;
    protected $table = 'condominiums'; // evitar pluralización irregular 'condominia'
    protected $fillable = ['name','active','subdomain','db_name'];
    protected $casts = ['active'=>'boolean'];
    public function towers(){return $this->hasMany(Tower::class);}    
    // Helper para saber si tiene BD dedicada
    public function hasDedicatedDatabase(): bool { return !empty($this->db_name); }
}
