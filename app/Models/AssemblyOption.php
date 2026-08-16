<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyOption extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = ['assembly_id', 'label', 'sort_order'];

    public function assembly()
    {
        return $this->belongsTo(Assembly::class);
    }

    public function votes()
    {
        return $this->hasMany(AssemblyVote::class, 'option_id');
    }
}