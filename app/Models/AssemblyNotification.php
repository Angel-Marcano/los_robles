<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyNotification extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = ['assembly_id', 'type', 'sent_at'];

    public $timestamps = false;

    protected $casts = ['sent_at' => 'datetime'];
}