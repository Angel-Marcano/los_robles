<?php
namespace App\Models; use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class ExchangeTransaction extends Model {use HasFactory; protected $fillable=['account_origin_id','account_target_id','rate','amount_origin_usd','amount_origin_ves','amount_target_usd','amount_target_ves']; protected $casts=['rate'=>'decimal:6','amount_origin_usd'=>'decimal:2','amount_origin_ves'=>'decimal:2','amount_target_usd'=>'decimal:2','amount_target_ves'=>'decimal:2']; }
