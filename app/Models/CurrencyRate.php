<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class CurrencyRate extends Model {use HasFactory, \App\Models\Traits\UsesTenantConnection; protected $fillable=['base','quote','rate','valid_from','valid_to','active']; protected $casts=['rate'=>'decimal:6','valid_from'=>'datetime','valid_to'=>'datetime','active'=>'boolean']; }
