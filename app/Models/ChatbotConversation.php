<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotConversation extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = [
        'user_id',
        'condominium_id',
        'channel',
        'session_id',
        'intent',
        'prompt_version',
        'input_raw',
        'input_sanitized',
        'output_raw',
        'output_sanitized',
        'tools_called',
        'actions_executed',
        'context',
        'tokens_input',
        'tokens_output',
        'model',
        'duration_ms',
        'needs_human',
        'is_action_pending',
        'pending_action',
        'pending_action_expires_at',
    ];

    protected $casts = [
        'tools_called' => 'array',
        'actions_executed' => 'array',
        'context' => 'array',
        'pending_action' => 'array',
        'needs_human' => 'boolean',
        'is_action_pending' => 'boolean',
        'pending_action_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }
}
