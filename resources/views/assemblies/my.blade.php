@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-card-checklist me-2"></i>Mis Votaciones</h1>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Título</th>
					<th>Tipo</th>
					<th>Estado</th>
					<th>Cierre</th>
					<th>Mi voto</th>
					<th class="text-end">Acción</th>
				</tr>
			</thead>
			<tbody>
				@forelse($assemblies as $a)
				@php $voted = $a->hasVoted(auth()->id()); @endphp
				<tr>
					<td class="fw-semibold">{{ $a->title }}</td>
					<td>
						@if($a->vote_type === 'secret')
							<span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculto</span>
						@else
							<span class="badge bg-info"><i class="bi bi-eye me-1"></i>Público</span>
						@endif
					</td>
					<td>
						@php $badgeClass = ['draft' => 'bg-secondary', 'open' => 'bg-success', 'closed' => 'bg-dark'][$a->status] ?? 'bg-secondary'; @endphp
						<span class="badge {{ $badgeClass }}">{{ $a->statusLabel() }}</span>
					</td>
					<td>{{ $a->closes_at?->format('d/m/Y H:i') ?? '—' }}</td>
					<td>
						@if($voted)
							<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Votado</span>
						@elseif($a->isOpen())
							<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pendiente</span>
						@else
							<span class="text-muted">—</span>
						@endif
					</td>
					<td class="text-end">
						<a href="{{ route('assemblies.show', $a) }}" class="btn btn-sm btn-outline-primary btn-action">
							@if($a->isOpen() && !$voted)
								<i class="bi bi-hand-thumbs-up"></i> Votar
							@else
								<i class="bi bi-eye"></i> Ver
							@endif
						</a>
					</td>
				</tr>
				@empty
				<tr><td colspan="6" class="text-center text-muted py-4">
					<i class="bi bi-inbox fs-2 d-block mb-2"></i>
					No hay votaciones disponibles.
				</td></tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
@if($assemblies->hasPages())
<div class="mt-3">{{ $assemblies->links() }}</div>
@endif
@endsection