<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'read'];
    protected $casts = ['read' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function iconClass(): string
    {
        return [
            'invoice_created' => 'bi-receipt text-primary',
            'payment_approved' => 'bi-check-circle text-success',
            'payment_rejected' => 'bi-x-circle text-danger',
            'reminder' => 'bi-clock text-warning',
            'overdue' => 'bi-exclamation-triangle text-danger',
            'info' => 'bi-info-circle text-info',
        ][$this->type] ?? 'bi-bell text-secondary';
    }
}