@extends('layouts.app')

@section('content')
@push('styles')
<style>
	.report-wide { width: 100%; }
	.report-wide th, .report-wide td { white-space: nowrap; }
	.month-cell { min-width: 70px; }
	.debt-highlight { color: var(--bs-danger); font-weight: 600; }
	@media print {
		@page { size: landscape; margin: 10mm; }
		.no-print { display: none !important; }
		.container { max-width: none !important; }
		body { font-size: 11px; }
	}
</style>
@endpush

<div class="d-flex justify-content-between align-items-center page-header no-print">
	<div>
		<h1><i class="bi bi-exclamation-triangle me-2"></i>Deudores por mes</h1>
		<div class="text-muted">Montos pendientes (USD equivalente) por apartamento</div>
	</div>
	<div class="d-flex align-items-center gap-2">
		{{-- Year navigation --}}
		<a class="btn btn-outline-secondary btn-sm btn-action" href="{{ route('reports.debtorsMonthly', ['year' => $year - 1]) }}">
			<i class="bi bi-chevron-left"></i>
		</a>
		<select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='{{ route('reports.debtorsMonthly') }}?year='+this.value">
			@for($y = now()->year + 1; $y >= now()->year - 5; $y--)
				<option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
			@endfor
		</select>
		<a class="btn btn-outline-secondary btn-sm btn-action" href="{{ route('reports.debtorsMonthly', ['year' => $year + 1]) }}">
			<i class="bi bi-chevron-right"></i>
		</a>
		<div class="vr mx-1"></div>
		<a class="btn btn-outline-secondary btn-sm btn-action" href="{{ route('invoices.index') }}">
			<i class="bi bi-arrow-left"></i> Volver
		</a>
		<a class="btn btn-primary btn-sm btn-action" href="{{ route('reports.debtorsMonthlyPdf', ['year' => $year]) }}" target="_blank">
			<i class="bi bi-file-earmark-pdf"></i> PDF
		</a>
	</div>
</div>

<div class="card">
	@if(count($rows) > 10)
	<div class="card-body py-2 border-bottom d-flex align-items-center gap-3 flex-wrap no-print">
		<div class="input-group input-group-sm" style="max-width:220px;">
			<span class="input-group-text"><i class="bi bi-search"></i></span>
			<input type="text" id="debtorSearch" class="form-control" placeholder="Buscar deudor...">
		</div>
		<div class="d-flex align-items-center gap-2">
			<label class="form-label mb-0 small text-muted">Mostrar</label>
			<select id="debtorPerPage" class="form-select form-select-sm" style="width:auto;">
				<option value="10">10</option>
				<option value="25">25</option>
				<option value="50">50</option>
				<option value="0">Todos</option>
			</select>
		</div>
		<span class="text-muted small">{{ count($rows) }} deudor(es)</span>
		<nav id="debtorPagination" class="ms-auto"></nav>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle report-wide mb-0">
			<thead>
				<tr>
					<th>Deudor</th>
					@foreach($months as $m => $label)
						<th class="text-end month-cell">{{ $label }}</th>
					@endforeach
					<th class="text-end">Total</th>
				</tr>
			</thead>
			<tbody id="debtorBody">
				@if(count($rows) === 0)
					<tr>
						<td colspan="{{ 1 + count($months) + 1 }}">
							<div class="empty-state">
								<i class="bi bi-check-circle"></i>
								<p>Sin deudores pendientes para {{ $year }}</p>
								<small class="text-muted">Usa las flechas para ver otros años</small>
							</div>
						</td>
					</tr>
				@else
					@foreach($rows as $r)
						<tr class="debtor-row" data-name="{{ strtolower(($r['apartment_code'] ?? '').' '.($r['owner_name'] ?? '')) }}">
							<td>
								<div>
									<span class="fw-semibold">{{ $r['apartment_code'] }}</span>
									@if(!empty($r['tower_name']))
										<span class="badge bg-light text-muted ms-1" style="font-size:.7rem">{{ $r['tower_name'] }}</span>
									@endif
								</div>
								@if(!empty($r['owner_name']))
									<div class="text-muted small"><i class="bi bi-person me-1"></i>{{ $r['owner_name'] }}</div>
								@endif
							</td>
							@foreach($months as $m => $label)
								@php($val = (float)($r['monthly'][$m] ?? 0))
								<td class="text-end {{ $val > 0 ? 'debt-highlight' : '' }}">{{ $val > 0 ? number_format($val, 2) : '' }}</td>
							@endforeach
							<td class="text-end"><strong class="debt-highlight">{{ number_format((float)$r['total'], 2) }}</strong></td>
						</tr>
					@endforeach
				@endif
			</tbody>
			@if(count($rows) > 0)
			<tfoot>
				<tr class="fw-bold">
					<td>Total general</td>
					@foreach($months as $m => $label)
						@php($colTotal = collect($rows)->sum(fn($r) => (float)($r['monthly'][$m] ?? 0)))
						<td class="text-end">{{ $colTotal > 0 ? number_format($colTotal, 2) : '' }}</td>
					@endforeach
					<td class="text-end">{{ number_format(collect($rows)->sum('total'), 2) }}</td>
				</tr>
			</tfoot>
			@endif
		</table>
	</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
	const rows = Array.from(document.querySelectorAll('#debtorBody .debtor-row'));
	if(rows.length <= 10) return;
	const searchInput = document.getElementById('debtorSearch');
	const perPageSelect = document.getElementById('debtorPerPage');
	const paginationNav = document.getElementById('debtorPagination');
	if(!searchInput) return;
	let currentPage = 1;

	function getFiltered(){
		const q = (searchInput.value || '').toLowerCase();
		if(!q) return rows;
		return rows.filter(r => (r.dataset.name||'').includes(q));
	}
	function render(){
		const filtered = getFiltered();
		const perPage = parseInt(perPageSelect.value) || 0;
		const totalPages = perPage > 0 ? Math.ceil(filtered.length / perPage) : 1;
		if(currentPage > totalPages) currentPage = totalPages || 1;
		rows.forEach(r => r.style.display = 'none');
		if(perPage === 0){
			filtered.forEach(r => r.style.display = '');
		} else {
			const start = (currentPage - 1) * perPage;
			filtered.slice(start, start + perPage).forEach(r => r.style.display = '');
		}
		if(totalPages <= 1){ paginationNav.innerHTML = ''; return; }
		let html = '<ul class="pagination pagination-sm mb-0">';
		html += '<li class="page-item '+(currentPage===1?'disabled':'')+'"><a class="page-link" href="#" data-p="'+(currentPage-1)+'">&laquo;</a></li>';
		let startP = Math.max(1, currentPage - 3), endP = Math.min(totalPages, startP + 6);
		if(endP - startP < 6) startP = Math.max(1, endP - 6);
		for(let p = startP; p <= endP; p++){
			html += '<li class="page-item '+(p===currentPage?'active':'')+'"><a class="page-link" href="#" data-p="'+p+'">'+p+'</a></li>';
		}
		html += '<li class="page-item '+(currentPage===totalPages?'disabled':'')+'"><a class="page-link" href="#" data-p="'+(currentPage+1)+'">&raquo;</a></li>';
		html += '</ul>';
		paginationNav.innerHTML = html;
		paginationNav.querySelectorAll('a[data-p]').forEach(a => {
			a.addEventListener('click', function(e){
				e.preventDefault();
				const p = parseInt(this.dataset.p);
				if(p >= 1 && p <= totalPages){ currentPage = p; render(); }
			});
		});
	}
	searchInput.addEventListener('input', function(){ currentPage = 1; render(); });
	perPageSelect.addEventListener('change', function(){ currentPage = 1; render(); });
	render();
});
</script>
@endpush
@endsection
