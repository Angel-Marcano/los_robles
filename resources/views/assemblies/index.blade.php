@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-card-checklist me-2"></i>Asambleas y Votaciones</h1>
	@if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin'))
	<a href="{{ route('assemblies.create') }}" class="btn btn-primary btn-action">
		<i class="bi bi-plus-lg"></i> Nueva votación
	</a>
	@endif
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Título</th>
					<th>Alcance</th>
					<th>Tipo</th>
					<th class="text-center">Votos</th>
					<th>Estado</th>
					<th>Cierre</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($assemblies as $a)
				<tr>
					<td>
						<a href="{{ route('assemblies.show', $a) }}" class="fw-semibold text-decoration-none">{{ $a->title }}</a>
						<br><small class="text-muted">{{ $a->voteTypeLabel() }}</small>
					</td>
					<td>{{ $a->scopeLabel() }}</td>
					<td>
						@if($a->vote_type === 'secret')
							<span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculto</span>
						@else
							<span class="badge bg-info"><i class="bi bi-eye me-1"></i>Público</span>
						@endif
					</td>
					<td class="text-center">
						<span class="badge bg-primary">{{ $a->votes_count }}</span>
					</td>
					<td>
						@php
							$badgeClass = ['draft' => 'bg-secondary', 'open' => 'bg-success', 'closed' => 'bg-dark'][$a->status] ?? 'bg-secondary';
						@endphp
						<span class="badge {{ $badgeClass }}">{{ $a->statusLabel() }}</span>
					</td>
					<td>{{ $a->closes_at?->format('d/m/Y H:i') ?? '—' }}</td>
					<td class="text-end">
						<a href="{{ route('assemblies.show', $a) }}" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-eye"></i> Ver</a>
						@if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin'))
							@if($a->status === 'draft')
								<a href="{{ route('assemblies.edit', $a) }}" class="btn btn-sm btn-outline-secondary btn-action"><i class="bi bi-pencil"></i></a>
							@elseif($a->status === 'open')
								<form style="display:inline" method="POST" action="{{ route('assemblies.close', $a) }}">@csrf @method('PATCH')
									<button class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('¿Cerrar votación?')"><i class="bi bi-stop-circle"></i></button>
								</form>
							@endif
							<form style="display:inline" method="POST" action="{{ route('assemblies.destroy', $a) }}">@csrf @method('DELETE')
								<button class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('¿Eliminar?')"><i class="bi bi-trash"></i></button>
							</form>
						@endif
					</td>
				</tr>
				@empty
				<tr><td colspan="7" class="text-center text-muted py-4">
					<i class="bi bi-inbox fs-2 d-block mb-2"></i>
					No hay votaciones registradas.
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