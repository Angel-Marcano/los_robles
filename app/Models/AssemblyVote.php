<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyVote extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = ['assembly_id', 'user_id', 'option_id', 'weight', 'voted_at'];

    protected $casts = [
        'weight'   => 'decimal:4',
        'voted_at' => 'datetime',
    ];

    public $timestamps = false;

    public function assembly()
    {
        return $this->belongsTo(Assembly::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function option()
    {
        return $this->belongsTo(AssemblyOption::class, 'option_id');
    }
}