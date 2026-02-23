<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class AccountMovement extends Model {use HasFactory, \App\Models\Traits\UsesTenantConnection; protected $fillable=['account_id','type','amount_usd','amount_ves','reference','user_id','meta']; protected $casts=['amount_usd'=>'decimal:2','amount_ves'=>'decimal:2','meta'=>'array']; }
