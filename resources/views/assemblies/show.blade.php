@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-card-checklist me-2"></i>{{ $assembly->title }}</h1>
	<a href="{{ route('assemblies.index') }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

@if(session('status'))
<div class="alert alert-success alert-dismissible fade show">
	<i class="bi bi-check-circle me-1"></i>{{ session('status') }}
	<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
	<ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-4">
	<div class="col-lg-8">
		<div class="card mb-3">
			<div class="card-header"><i class="bi bi-info-circle me-1"></i>Detalles</div>
			<div class="card-body">
				<table class="table table-borderless table-sm mb-0">
					<tr>
						<th style="width:160px">Alcance</th>
						<td>{{ $assembly->scopeLabel() }}</td>
					</tr>
					@if($assembly->scope === 'tower' && !empty($assembly->tower_ids))
					<tr>
						<th>Torres</th>
						<td>{{ implode(', ', \App\Models\Tower::whereIn('id', $assembly->tower_ids)->pluck('name')->toArray()) }}</td>
					</tr>
					@endif
					<tr>
						<th>Tipo de voto</th>
						<td>{{ $assembly->voteTypeLabel() }}</td>
					</tr>
					<tr>
						<th>Quórum</th>
						<td>
							@if($assembly->quorum_type === 'none')
								Sin límite
							@else
								{{ $assembly->quorum_type === 'qualified' ? 'Calificada' : 'Simple' }} — {{ $assembly->quorum_value }}%
							@endif
						</td>
					</tr>
					<tr>
						<th>Peso</th>
						<td>{{ $assembly->weight_mode === 'aliquot' ? 'Por alícuota' : '1 voto por apartamento' }}</td>
					</tr>
					<tr>
						<th>Cierre</th>
						<td>{{ $assembly->closes_at?->format('d/m/Y H:i') ?? 'Manual' }}</td>
					</tr>
					<tr>
						<th>Estado</th>
						<td>
							@php $badgeClass = ['draft' => 'bg-secondary', 'open' => 'bg-success', 'closed' => 'bg-dark'][$assembly->status] ?? 'bg-secondary'; @endphp
							<span class="badge {{ $badgeClass }}">{{ $assembly->statusLabel() }}</span>
						</td>
					</tr>
					@if($assembly->description)
					<tr>
						<th>Descripción</th>
						<td>{{ $assembly->description }}</td>
					</tr>
					@endif
				</table>
			</div>
		</div>

		{{-- Votación --}}
		@if($assembly->isOpen() && !$hasVoted)
			<div class="card border-primary">
				<div class="card-header bg-primary text-white"><i class="bi bi-hand-thumbs-up me-1"></i>Emite tu voto</div>
				<div class="card-body">
					<form method="POST" action="{{ route('assemblies.vote', $assembly) }}">
						@csrf
						@foreach($assembly->options as $opt)
						<div class="form-check mb-2">
							<input class="form-check-input" type="radio" name="option_id" value="{{ $opt->id }}" id="opt_{{ $opt->id }}" required>
							<label class="form-check-label fs-5" for="opt_{{ $opt->id }}">{{ $opt->label }}</label>
						</div>
						@endforeach
						<button class="btn btn-primary btn-action mt-2"><i class="bi bi-check-lg"></i> Votar</button>
					</form>
				</div>
			</div>
		@elseif($hasVoted)
			<div class="alert alert-success">
				<i class="bi bi-check-circle me-1"></i> Ya emitiste tu voto.
				@if($assembly->vote_type === 'public' && $myVote)
					<br><span class="small">Tu voto: <strong>{{ $myVote->option->label }}</strong></span>
				@endif
			</div>
		@elseif($assembly->status === 'draft')
			<div class="alert alert-secondary">
				<i class="bi bi-hourglass me-1"></i> Esta votación aún no está abierta.
			</div>
		@elseif($assembly->isClosed())
			<div class="alert alert-dark">
				<i class="bi bi-lock me-1"></i> Esta votación está cerrada.
			</div>
		@endif

		{{-- Resultados --}}
		@if($assembly->isClosed() || ($assembly->vote_type === 'public' && $assembly->status === 'open'))
			<div class="card mt-3">
				<div class="card-header"><i class="bi bi-bar-chart me-1"></i>Resultados</div>
				<div class="card-body">
					@if($assembly->vote_type === 'secret' && !$assembly->isClosed())
						<p class="text-muted">Los resultados de una votación oculta se muestran solo al cerrar.</p>
					@else
						@foreach($results as $r)
						<div class="mb-3">
							<div class="d-flex justify-content-between mb-1">
								<span class="fw-semibold">{{ $r['label'] }}</span>
								<span class="text-muted small">{{ $r['votes'] }} votos · {{ $r['percentage'] }}%</span>
							</div>
							<div class="progress" style="height:8px">
								<div class="progress-bar" style="width:{{ $r['percentage'] }}%"></div>
							</div>
						</div>
						@endforeach
						<hr>
						<div class="d-flex justify-content-between">
							<span>Total de votos: <strong>{{ $assembly->totalVotes() }}</strong></span>
							<span>Quórum: <strong>{{ $assembly->quorumReached() ? 'Alcanzado' : 'No alcanzado' }}</strong></span>
						</div>
					@endif
				</div>
			</div>
		@endif
	</div>

	<div class="col-lg-4">
		{{-- Participación --}}
		<div class="card mb-3">
			<div class="card-header"><i class="bi bi-people me-1"></i>Participación</div>
			<div class="card-body">
				<div class="d-flex justify-content-between mb-2">
					<span class="text-muted">Habilitados</span>
					<span class="fw-semibold">{{ $eligibleCount }}</span>
				</div>
				<div class="d-flex justify-content-between mb-2">
					<span class="text-muted">Han votado</span>
					<span class="fw-semibold text-success">{{ $assembly->totalVotes() }}</span>
				</div>
				<div class="d-flex justify-content-between mb-3">
					<span class="text-muted">Pendientes</span>
					<span class="fw-semibold text-warning">{{ max(0, $eligibleCount - $assembly->totalVotes()) }}</span>
				</div>
				<div class="progress mb-1" style="height:10px">
					@php $pct = $eligibleCount > 0 ? round(($assembly->totalVotes() / $eligibleCount) * 100) : 0; @endphp
					<div class="progress-bar bg-success" style="width:{{ $pct }}%">{{ $pct }}%</div>
				</div>
			</div>
		</div>

		{{-- Acciones admin --}}
		@if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin'))
		<div class="card">
			<div class="card-header"><i class="bi bi-gear me-1"></i>Acciones</div>
			<div class="card-body d-grid gap-2">
				@if($assembly->status === 'draft')
					<form method="POST" action="{{ route('assemblies.open', $assembly) }}">@csrf @method('PATCH')
						<button class="btn btn-success btn-action w-100"><i class="bi bi-play-circle"></i> Abrir votación</button>
					</form>
					<a href="{{ route('assemblies.edit', $assembly) }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-pencil"></i> Editar</a>
				@elseif($assembly->status === 'open')
					<form method="POST" action="{{ route('assemblies.close', $assembly) }}">@csrf @method('PATCH')
						<button class="btn btn-danger btn-action w-100" onclick="return confirm('¿Cerrar votación?')"><i class="bi bi-stop-circle"></i> Cerrar votación</button>
					</form>
				@endif
			</div>
		</div>
		@endif
	</div>
</div>
@endsection