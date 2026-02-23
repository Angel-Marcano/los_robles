<?php
namespace App\Models; use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Ownership extends Model {use HasFactory, \App\Models\Traits\UsesTenantConnection; protected $fillable=['apartment_id','user_id','role','active']; protected $casts=['active'=>'boolean']; public function user(){return $this->belongsTo(User::class);} public function apartment(){return $this->belongsTo(Apartment::class);} }
