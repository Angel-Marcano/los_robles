@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-journal-text me-2"></i>Auditoría</h1>
	</div>
	<a href="{{url('audit-logs') . '?' . http_build_query(array_merge(request()->query(),['export'=>'csv']))}}" class="btn btn-outline-primary btn-action"><i class="bi bi-download"></i> Exportar CSV</a>
</div>

<div class="card mb-4">
	<div class="card-body">
		<form method="GET">
			<div class="row g-3">
				<div class="col-md-3">
					<label class="form-label">Entidad</label>
					<select name="entity_type" class="form-select">
						<option value="">-- Todas --</option>
						@foreach($distinctTypes as $t)
							<option value="{{$t}}" @if(request('entity_type')===$t) selected @endif>{{$t}}</option>
						@endforeach
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label">Acción</label>
					<select name="action" class="form-select">
						<option value="">-- Todas --</option>
						@foreach($distinctActions as $a)
							<option value="{{$a}}" @if(request('action')===$a) selected @endif>{{$a}}</option>
						@endforeach
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label">Usuario</label>
					<select name="user_id" class="form-select">
						<option value="">-- Todos --</option>
						@foreach($users as $u)
							<option value="{{$u->id}}" @if(request('user_id')==$u->id) selected @endif>{{$u->name}}</option>
						@endforeach
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label">Desde</label>
					<input type="date" name="date_from" value="{{request('date_from')}}" class="form-control">
				</div>
				<div class="col-md-2">
					<label class="form-label">Hasta</label>
					<input type="date" name="date_to" value="{{request('date_to')}}" class="form-control">
				</div>
				<div class="col-md-1">
					<label class="form-label">Págs</label>
					<select name="per_page" class="form-select">
						<option value="10" @if($perPage==10) selected @endif>10</option>
						<option value="20" @if($perPage==20) selected @endif>20</option>
						<option value="50" @if($perPage==50) selected @endif>50</option>
					</select>
				</div>
				<div class="col-12 d-flex gap-2">
					<button class="btn btn-primary btn-action"><i class="bi bi-funnel"></i> Filtrar</button>
					<a href="{{url('audit-logs')}}" class="btn btn-outline-secondary btn-action"><i class="bi bi-x-lg"></i> Limpiar</a>
				</div>
			</div>
		</form>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>Fecha</th>
					<th>Usuario</th>
					<th>Entidad</th>
					<th>Acción</th>
					<th>ID Entidad</th>
					<th>IP</th>
					<th>Cambios</th>
				</tr>
			</thead>
			<tbody>
				@forelse($logs as $log)
					<tr>
						<td class="text-muted">{{$log->id}}</td>
						<td class="small">{{$log->created_at}}</td>
						<td>{{$log->user_id}}</td>
						<td><span class="badge bg-light text-dark">{{$log->entity_type}}</span></td>
						<td><span class="badge bg-info text-dark">{{$log->action}}</span></td>
						<td>{{$log->entity_id}}</td>
						<td class="small text-muted">{{$log->ip}}</td>
						<td><pre class="mb-0 small" style="white-space:pre-wrap;max-width:300px;overflow:auto;">{{ json_encode($log->changes,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td>
					</tr>
				@empty
					<tr>
						<td colspan="8">
							<div class="empty-state">
								<i class="bi bi-journal-text"></i>
								<p>Sin registros de auditoría</p>
							</div>
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($logs->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $logs->firstItem() }}–{{ $logs->lastItem() }} de {{ $logs->total() }}</div>
	{{ $logs->links() }}
</div>
@endif
@endsection
