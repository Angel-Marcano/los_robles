@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-door-open me-2"></i>Apartamentos — {{ $tower->name }}</h1>
	</div>
	<div class="d-flex gap-2">
		<a class="btn btn-outline-secondary btn-action" href="{{ route('towers.index') }}"><i class="bi bi-arrow-left"></i> Torres</a>
		<a class="btn btn-primary btn-action" href="{{ route('towers.apartments.create',$tower) }}"><i class="bi bi-plus-lg"></i> Nuevo Apartamento</a>
	</div>
</div>

{{-- Bulk actions bar (hidden until selection) --}}
<form id="bulk-form" method="POST" action="{{ route('apartments.bulkDestroy', $tower) }}" class="d-none mb-3">
	@csrf @method('DELETE')
	<div class="d-flex align-items-center gap-3 p-2 bg-body-secondary rounded">
		<button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll"><i class="bi bi-check-all"></i> Seleccionar todos</button>
		<button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAll"><i class="bi bi-x-lg"></i> Limpiar selección</button>
		<span class="text-muted small">Seleccionados: <strong id="selectedCount">0</strong></span>
		<button type="submit" class="btn btn-sm btn-danger ms-auto" onclick="return confirm('¿Eliminar los apartamentos seleccionados?')"><i class="bi bi-trash"></i> Borrar seleccionados</button>
	</div>
</form>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th style="width:40px;"><input type="checkbox" class="form-check-input" id="chkAll" title="Seleccionar todos"></th>
					<th>ID</th>
					<th>Código</th>
					<th>Alícuota (%)</th>
					<th>Estado</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($apartments as $a)
					<tr>
						<td><input type="checkbox" class="form-check-input row-check" value="{{ $a->id }}"></td>
						<td class="text-muted">{{ $a->id }}</td>
						<td class="fw-semibold">{{ $a->code }}</td>
						<td>{{ $a->aliquot_percent }}%</td>
						<td>
							@if($a->active)
								<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
							@else
								<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
							@endif
						</td>
						<td class="text-end">
							<a class="btn btn-sm btn-outline-primary btn-action me-1" href="{{ route('apartments.edit',$a) }}"><i class="bi bi-pencil"></i> Editar</a>
							<form style="display:inline" method="POST" action="{{ route('apartments.destroy',$a) }}">
								@csrf @method('DELETE')
								<button class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('¿Eliminar este apartamento?')"><i class="bi bi-trash"></i> Borrar</button>
							</form>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="6">
							<div class="empty-state">
								<i class="bi bi-door-open"></i>
								<p>No hay apartamentos registrados en esta torre</p>
							</div>
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($apartments->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $apartments->firstItem() }}–{{ $apartments->lastItem() }} de {{ $apartments->total() }}</div>
	{{ $apartments->links() }}
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
	const bulkForm = document.getElementById('bulk-form');
	const chkAll = document.getElementById('chkAll');
	const btnSelectAll = document.getElementById('btnSelectAll');
	const btnClearAll = document.getElementById('btnClearAll');
	const countEl = document.getElementById('selectedCount');
	const rowChecks = document.querySelectorAll('.row-check');

	function syncBulkForm() {
		const checked = document.querySelectorAll('.row-check:checked');
		const count = checked.length;
		countEl.textContent = count;
		bulkForm.classList.toggle('d-none', count === 0);
		chkAll.checked = rowChecks.length > 0 && count === rowChecks.length;
		chkAll.indeterminate = count > 0 && count < rowChecks.length;
		// Sync hidden inputs
		bulkForm.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
		checked.forEach(cb => {
			const h = document.createElement('input');
			h.type = 'hidden'; h.name = 'ids[]'; h.value = cb.value;
			bulkForm.appendChild(h);
		});
	}

	chkAll.addEventListener('change', function() {
		rowChecks.forEach(cb => cb.checked = chkAll.checked);
		syncBulkForm();
	});

	rowChecks.forEach(cb => cb.addEventListener('change', syncBulkForm));

	btnSelectAll.addEventListener('click', function() {
		rowChecks.forEach(cb => cb.checked = true);
		syncBulkForm();
	});

	btnClearAll.addEventListener('click', function() {
		rowChecks.forEach(cb => cb.checked = false);
		syncBulkForm();
	});
});
</script>
@endpush
@endsection
