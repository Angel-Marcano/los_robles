<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assembly extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;

    protected $fillable = [
        'title', 'description', 'scope', 'tower_ids',
        'vote_type', 'quorum_type', 'quorum_value', 'weight_mode',
        'closes_at', 'status', 'created_by',
    ];

    protected $casts = [
        'tower_ids'  => 'array',
        'closes_at'  => 'datetime',
        'quorum_value' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options()
    {
        return $this->hasMany(AssemblyOption::class)->orderBy('sort_order');
    }

    public function votes()
    {
        return $this->hasMany(AssemblyVote::class);
    }

    public function notifications()
    {
        return $this->hasMany(AssemblyNotification::class);
    }

    // ¿Está abierta para votar?
    public function isOpen(): bool
    {
        return $this->status === 'open'
            && ($this->closes_at === null || $this->closes_at->isFuture());
    }

    // ¿Ya cerró?
    public function isClosed(): bool
    {
        return $this->status === 'closed'
            || ($this->status === 'open' && $this->closes_at && $this->closes_at->isPast());
    }

    // ¿El usuario ya votó?
    public function hasVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    // Total de votos emitidos
    public function totalVotes(): int
    {
        return $this->votes()->count();
    }

    // Resultados agrupados por opción
    public function results(): array
    {
        $results = [];
        $totalWeight = 0;
        foreach ($this->options as $opt) {
            $weight = $this->votes()->where('option_id', $opt->id)->sum('weight');
            $count = $this->votes()->where('option_id', $opt->id)->count();
            $results[] = [
                'option_id' => $opt->id,
                'label'     => $opt->label,
                'votes'     => $count,
                'weight'    => round((float) $weight, 4),
            ];
            $totalWeight += $weight;
        }
        foreach ($results as &$r) {
            $r['percentage'] = $totalWeight > 0 ? round(($r['weight'] / $totalWeight) * 100, 1) : 0;
        }
        return $results;
    }

    // Usuarios habilitados para votar (owners y co_owners del alcance)
    public function eligibleVoters()
    {
        $query = User::whereHas('ownerships', function ($q) {
            $q->where('active', true)->whereIn('role', ['owner', 'co_owner']);
        });

        if ($this->scope === 'tower' && !empty($this->tower_ids)) {
            $query->whereHas('ownerships.apartment', function ($q) {
                $q->whereIn('tower_id', $this->tower_ids);
            });
        }

        return $query->get();
    }

    public function eligibleVotersCount(): int
    {
        return $this->eligibleVoters()->count();
    }

    // ¿Se alcanzó el quórum?
    public function quorumReached(): bool
    {
        if ($this->quorum_type === 'none') return true;
        $eligible = $this->eligibleVotersCount();
        if ($eligible === 0) return false;
        $voted = $this->totalVotes();
        $pct = ($voted / $eligible) * 100;
        return $pct >= (float) $this->quorum_value;
    }

    public function statusLabel(): string
    {
        return [
            'draft'  => 'Borrador',
            'open'   => 'Abierta',
            'closed' => 'Cerrada',
        ][$this->status] ?? '—';
    }

    public function scopeLabel(): string
    {
        return $this->scope === 'tower' ? 'Por Torre' : 'Condominio completo';
    }

    public function voteTypeLabel(): string
    {
        return $this->vote_type === 'secret' ? 'Voto oculto' : 'Voto público';
    }
}