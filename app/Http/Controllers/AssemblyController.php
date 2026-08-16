<?php

namespace App\Http\Controllers;

use App\Models\Assembly;
use App\Models\AssemblyOption;
use App\Models\AssemblyVote;
use App\Models\Tower;
use App\Models\Ownership;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AssemblyController extends Controller
{
    // ===== Admin =====

    public function index()
    {
        $assemblies = Assembly::withCount('votes')->orderByDesc('id')->paginate(15);
        return view('assemblies.index', compact('assemblies'));
    }

    public function create()
    {
        $towers = Tower::orderBy('name')->get();
        return view('assemblies.create', compact('towers'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string|max:2000',
            'scope'        => 'required|in:condo,tower',
            'tower_ids'    => 'nullable|array',
            'tower_ids.*'  => 'integer|exists:towers,id',
            'vote_type'    => 'required|in:public,secret',
            'quorum_type'  => 'required|in:none,simple,qualified',
            'quorum_value' => 'nullable|numeric|min:0|max:100',
            'weight_mode'  => 'required|in:equal,aliquot',
            'closes_at'    => 'nullable|date|after:now',
            'status'       => 'required|in:draft,open',
            'options'      => 'required|array|min:2',
            'options.*'    => 'required|string|max:120',
        ]);

        $assembly = Assembly::create([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'scope'        => $data['scope'],
            'tower_ids'    => $data['scope'] === 'tower' ? ($data['tower_ids'] ?? []) : null,
            'vote_type'    => $data['vote_type'],
            'quorum_type'  => $data['quorum_type'],
            'quorum_value' => $data['quorum_type'] === 'none' ? 0 : ($data['quorum_value'] ?? 50),
            'weight_mode'  => $data['weight_mode'],
            'closes_at'    => $data['closes_at'] ?? null,
            'status'       => $data['status'],
            'created_by'   => auth()->id(),
        ]);

        $order = 0;
        foreach ($data['options'] as $label) {
            AssemblyOption::create([
                'assembly_id' => $assembly->id,
                'label'       => $label,
                'sort_order'  => $order++,
            ]);
        }

        // Si se abre directamente, notificar a los involucrados
        if ($assembly->status === 'open') {
            $this->notifyEligible($assembly);
        }

        return redirect()->route('assemblies.show', $assembly)
            ->with('status', 'Asamblea creada correctamente.');
    }

    public function show(Assembly $assembly)
    {
        $assembly->load('options', 'votes', 'creator');
        $eligibleCount = $assembly->eligibleVotersCount();
        $results = $assembly->results();
        $hasVoted = auth()->check() ? $assembly->hasVoted(auth()->id()) : false;
        $myVote = $hasVoted ? $assembly->votes()->where('user_id', auth()->id())->first() : null;

        return view('assemblies.show', compact('assembly', 'eligibleCount', 'results', 'hasVoted', 'myVote'));
    }

    public function edit(Assembly $assembly)
    {
        if ($assembly->status !== 'draft') {
            return redirect()->route('assemblies.show', $assembly)
                ->with('status', 'No se puede editar una asamblea que ya está abierta o cerrada.');
        }
        $towers = Tower::orderBy('name')->get();
        $assembly->load('options');
        return view('assemblies.edit', compact('assembly', 'towers'));
    }

    public function update(Request $r, Assembly $assembly)
    {
        if ($assembly->status !== 'draft') {
            return redirect()->route('assemblies.show', $assembly)
                ->with('status', 'No se puede editar una asamblea que ya está abierta o cerrada.');
        }

        $data = $r->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string|max:2000',
            'scope'        => 'required|in:condo,tower',
            'tower_ids'    => 'nullable|array',
            'tower_ids.*'  => 'integer|exists:towers,id',
            'vote_type'    => 'required|in:public,secret',
            'quorum_type'  => 'required|in:none,simple,qualified',
            'quorum_value' => 'nullable|numeric|min:0|max:100',
            'weight_mode'  => 'required|in:equal,aliquot',
            'closes_at'    => 'nullable|date|after:now',
            'options'      => 'required|array|min:2',
            'options.*'    => 'required|string|max:120',
        ]);

        $assembly->update([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'scope'        => $data['scope'],
            'tower_ids'    => $data['scope'] === 'tower' ? ($data['tower_ids'] ?? []) : null,
            'vote_type'    => $data['vote_type'],
            'quorum_type'  => $data['quorum_type'],
            'quorum_value' => $data['quorum_type'] === 'none' ? 0 : ($data['quorum_value'] ?? 50),
            'weight_mode'  => $data['weight_mode'],
            'closes_at'    => $data['closes_at'] ?? null,
        ]);

        // Sincronizar opciones
        $assembly->options()->delete();
        $order = 0;
        foreach ($data['options'] as $label) {
            AssemblyOption::create([
                'assembly_id' => $assembly->id,
                'label'       => $label,
                'sort_order'  => $order++,
            ]);
        }

        return redirect()->route('assemblies.show', $assembly)
            ->with('status', 'Asamblea actualizada.');
    }

    public function open(Assembly $assembly)
    {
        if ($assembly->status !== 'draft') {
            return back()->with('status', 'Solo se pueden abrir asambleas en borrador.');
        }
        $assembly->update(['status' => 'open']);
        $this->notifyEligible($assembly);

        return redirect()->route('assemblies.show', $assembly)
            ->with('status', 'Asamblea abierta. Se notificó a los involucrados.');
    }

    public function close(Assembly $assembly)
    {
        if ($assembly->status !== 'open') {
            return back()->with('status', 'Solo se pueden cerrar asambleas abiertas.');
        }
        $assembly->update(['status' => 'closed']);

        // Notificar resultados
        $eligible = $assembly->eligibleVoters();
        foreach ($eligible as $user) {
            NotificationService::notify(
                $user->id,
                'info',
                'Resultados: ' . $assembly->title,
                'La votación ha cerrado. Revisa los resultados.',
                route('assemblies.show', $assembly)
            );
        }

        return redirect()->route('assemblies.show', $assembly)
            ->with('status', 'Asamblea cerrada. Se notificaron los resultados.');
    }

    public function destroy(Assembly $assembly)
    {
        $assembly->delete();
        return redirect()->route('assemblies.index')->with('status', 'Asamblea eliminada.');
    }

    // ===== Votación =====

    public function vote(Request $r, Assembly $assembly)
    {
        if (!$assembly->isOpen()) {
            return back()->withErrors('Esta votación no está abierta.');
        }

        $user = auth()->user();

        // Verificar que es owner o co_owner
        $ownership = Ownership::where('user_id', $user->id)
            ->where('active', true)
            ->whereIn('role', ['owner', 'co_owner']);

        if ($assembly->scope === 'tower' && !empty($assembly->tower_ids)) {
            $ownership->whereHas('apartment', function ($q) use ($assembly) {
                $q->whereIn('tower_id', $assembly->tower_ids);
            });
        }

        if (!$ownership->exists()) {
            return back()->withErrors('No estás habilitado para votar en esta asamblea.');
        }

        if ($assembly->hasVoted($user->id)) {
            return back()->withErrors('Ya has votado en esta asamblea.');
        }

        $data = $r->validate([
            'option_id' => 'required|exists:assembly_options,id',
        ]);

        // Calcular peso
        $weight = 1.0;
        if ($assembly->weight_mode === 'aliquot') {
            $apt = $user->ownerships()
                ->where('active', true)
                ->whereIn('role', ['owner', 'co_owner'])
                ->first();
            if ($apt && $apt->apartment) {
                $weight = (float) $apt->apartment->aliquot_percent;
            }
        }

        AssemblyVote::create([
            'assembly_id' => $assembly->id,
            'user_id'     => $user->id,
            'option_id'   => $data['option_id'],
            'weight'      => $weight,
            'voted_at'    => now(),
        ]);

        return redirect()->route('assemblies.show', $assembly)
            ->with('status', '¡Voto emitido correctamente!');
    }

    // ===== Panel del propietario =====

    public function myAssemblies()
    {
        $user = auth()->user();
        $assemblies = Assembly::where('status', '!=', 'draft')
            ->orderByDesc('id')
            ->paginate(10);

        return view('assemblies.my', compact('assemblies'));
    }

    // ===== Notificaciones =====

    private function notifyEligible(Assembly $assembly): void
    {
        $eligible = $assembly->eligibleVoters();
        foreach ($eligible as $user) {
            NotificationService::notify(
                $user->id,
                'info',
                'Nueva votación: ' . $assembly->title,
                $assembly->description ?? 'Tienes una votación pendiente.',
                route('assemblies.show', $assembly)
            );
        }

        \App\Models\AssemblyNotification::create([
            'assembly_id' => $assembly->id,
            'type'        => 'summon',
            'sent_at'     => now(),
        ]);
    }
}