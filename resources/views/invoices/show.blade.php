@extends('layouts.app')
@section('content')
	@php
		$currentRate = \App\Models\CurrencyRate::where('active', true)->orderByDesc('valid_from')->first();
		$lateUsd = $invoice->computeLateFeeUsd();
		$effectiveRate = $currentRate->rate ?? $invoice->exchange_rate_used;
		$visibleItems = isset($items) ? $items : $invoice->items;
		$myTotalUsd = round($visibleItems->sum('subtotal_usd'), 2);
		$myTotalVes = round($visibleItems->sum('subtotal_ves'), 2);
		$totalVesVar = number_format($myTotalUsd * $effectiveRate, 2);
	@endphp

	<div class="d-flex align-items-center justify-content-between page-header">
		<div>
			<h1><i class="bi bi-receipt me-2"></i>Factura #{{ $invoice->id }}</h1>
			<div class="text-muted">Periodo: {{ $invoice->period }} | Vence: {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/D' }}</div>
		</div>
		<div>
			@php
				$badgeClass = match($invoice->status) {
					'paid' => 'success',
					'pending' => 'warning',
					default => 'secondary',
				};
			@endphp
			<span class="badge bg-{{ $badgeClass }} fs-6">{{ $invoice->statusLabel() }}</span>
		</div>
	</div>

	<div class="card mb-3">
		<div class="card-body">
			@if($invoice->parent_id)
				<div class="alert alert-info py-2 mb-3">
					Factura individual del apartamento
					@if($invoice->apartment)
						<strong>{{ $invoice->apartment->code }}</strong>
					@endif
					(generada desde la factura #{{ $invoice->parent_id }}).
					@if($invoice->owner_name)
						<br><i class="bi bi-person me-1"></i>Propietario: <strong>{{ $invoice->owner_name }}</strong>
						@if($invoice->owner_document) — {{ $invoice->owner_document }} @endif
						@if($invoice->owner_email) — {{ $invoice->owner_email }} @endif
					@endif
				</div>
			@endif
			<div class="row g-3">
				<div class="col-12 col-md-6">
					<div class="d-flex justify-content-between"><span class="text-muted">Total USD:</span><strong>{{ number_format($myTotalUsd,2) }}</strong></div>
					<div class="d-flex justify-content-between"><span class="text-muted">Total VES:</span>
						<strong>
							@if($invoice->status==='paid')
								{{ number_format($myTotalVes,2) }} <span class="text-muted">(tasa pagada {{ number_format((float)$invoice->paid_exchange_rate, 2) }})</span>
							@else
								{{ $totalVesVar }} <span class="text-muted">(variable)</span>
							@endif
						</strong>
					</div>
				</div>
				<div class="col-12 col-md-6">
					@php
						$lateVes = $invoice->status === 'paid'
							? $invoice->late_fee_accrued_ves
							: ($lateUsd * $effectiveRate);
						$share = $invoice->total_usd > 0 ? ($myTotalUsd / $invoice->total_usd) : 0;
						$myLateUsd = round($lateUsd * $share, 2);
						$myLateVes = round($lateVes * $share, 2);
						$grandUsd = $myTotalUsd + $myLateUsd;
						$grandVes = $myTotalVes + $myLateVes;
						$paymentReportsForBalance = $invoice->paymentReports ?? collect();
						$approvedPaidUsdEq = 0.0;
						foreach ($paymentReportsForBalance as $pr) {
							if (($pr->status ?? null) !== 'approved') {
								continue;
							}
							$usd = (float) ($pr->amount_usd ?? 0);
							$ves = (float) ($pr->amount_ves ?? 0);
							$r = (float) ($pr->exchange_rate_used ?? 0);
							$vesInUsd = ($r > 0) ? ($ves / $r) : 0.0;
							$approvedPaidUsdEq += ($usd + $vesInUsd);
						}
						$remainingUsdEq = max(0.0, round((float)$grandUsd - (float)$approvedPaidUsdEq, 2));
					@endphp
					<div class="d-flex justify-content-between"><span class="text-muted">Mora USD:</span><strong>{{ number_format($myLateUsd,2) }}</strong></div>
					<div class="d-flex justify-content-between"><span class="text-muted">Mora VES:</span><strong>{{ number_format($myLateVes,2) }}</strong></div>
					<hr class="my-2" />
					<div class="d-flex justify-content-between"><span class="text-muted">Total + Mora USD:</span><strong>{{ number_format($grandUsd,2) }}</strong></div>
					<div class="d-flex justify-content-between"><span class="text-muted">Total + Mora VES:</span><strong>{{ number_format($grandVes,2) }}</strong></div>
					@if(($invoice->paymentReports ?? collect())->count() > 0 || $invoice->status !== 'draft')
						<hr class="my-2" />
						<div class="d-flex justify-content-between"><span class="text-muted">Abonos aprobados (USD equiv):</span><strong>{{ number_format($approvedPaidUsdEq,2) }}</strong></div>
						<div class="d-flex justify-content-between"><span class="text-muted">Saldo pendiente (USD equiv):</span><strong>{{ number_format($remainingUsdEq,2) }}</strong></div>
					@endif
				</div>
			</div>
		</div>
	</div>

	<div class="d-flex flex-wrap gap-2 mb-3">
		<a class="btn btn-outline-primary btn-action" href="{{ route('invoices.pdf',$invoice) }}" target="_blank"><i class="bi bi-file-pdf"></i> Ver PDF</a>
		@can('update', $invoice)
			@if($invoice->status==='draft')
				<a class="btn btn-primary btn-action" href="{{ route('invoices.edit',$invoice) }}"><i class="bi bi-pencil"></i> Editar</a>
			@endif
		@endcan
		@can('create', App\Models\Invoice::class)
		@if($invoice->status==='draft')
			<form method="POST" action="{{ route('invoices.approve',$invoice) }}">@csrf @method('PATCH')
				<button class="btn btn-success" onclick="return confirm('¿Aprobar factura y notificar a propietarios?')">Aprobar</button>
			</form>
		@endif
		@if($invoice->status==='pending')
			@php
				$reportedSelf = ($invoice->paymentReports ?? collect())->where('status', 'reported')->count() > 0;
				$reportedChildren = false;
				if(!$invoice->parent_id && ($invoice->children ?? collect())->count() > 0){
					foreach(($invoice->children ?? collect()) as $ch){
						if(($ch->paymentReports ?? collect())->where('status', 'reported')->count() > 0){
							$reportedChildren = true;
							break;
						}
					}
				}
			@endphp
			@if($reportedSelf || $reportedChildren)
				<div class="alert alert-warning py-2 mb-0 flex-grow-1">
					<small>
						Hay abonos <strong>reportados</strong> pendientes de revisión.
						Aprobar/Rechazar esos abonos antes de poder <strong>Marcar Pagada</strong>.
					</small>
				</div>
			@endif
			@if(isset($isParent) && $isParent)
				@if(isset($allChildrenPaid) && !$allChildrenPaid)
					@if(!($reportedSelf || $reportedChildren))
						<form method="POST" action="{{ route('invoices.markPaid',$invoice) }}">@csrf @method('PATCH')
							<input type="hidden" name="cascade" value="1" />
							<button class="btn btn-warning" onclick="return confirm('Esto marcará como pagadas TODAS las sub-facturas y la factura padre. ¿Confirmas?')">Marcar pagada (padre e hijas)</button>
						</form>
					@endif
				@else
					@if(!($reportedSelf || $reportedChildren))
						<form method="POST" action="{{ route('invoices.markPaid',$invoice) }}">@csrf @method('PATCH')
							<button class="btn btn-warning" onclick="return confirm('¿Marcar como pagada?')">Marcar Pagada</button>
						</form>
					@endif
				@endif
			@else
				@if(!($reportedSelf || $reportedChildren))
					<form method="POST" action="{{ route('invoices.markPaid',$invoice) }}">@csrf @method('PATCH')
						<button class="btn btn-warning" onclick="return confirm('¿Marcar como pagada?')">Marcar Pagada</button>
					</form>
				@endif
			@endif
		@endif
		@endcan
		@if($invoice->status==='pending')
			<a class="btn btn-info" href="{{ route('payments.create',$invoice) }}">Registrar abono</a>
		@endif
	</div>

	<div class="card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<span><i class="bi bi-list-ul me-1"></i>Detalle de ítems</span>
			<span class="text-muted small">{{ $visibleItems->count() }} ítem(s)</span>
		</div>
		@if($visibleItems->count() > 10)
		<div class="card-body py-2 border-bottom d-flex align-items-center gap-3 flex-wrap">
			<div class="input-group input-group-sm" style="max-width:220px;">
				<span class="input-group-text"><i class="bi bi-search"></i></span>
				<input type="text" id="itemSearch" class="form-control" placeholder="Buscar apto o concepto...">
			</div>
			<div class="d-flex align-items-center gap-2">
				<label class="form-label mb-0 small text-muted">Mostrar</label>
				<select id="itemsPerPage" class="form-select form-select-sm" style="width:auto;">
					<option value="10">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="0">Todos</option>
				</select>
			</div>
			<nav id="itemsPagination" class="ms-auto"></nav>
		</div>
		@endif
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>Apto</th>
							<th>Concepto</th>
							<th>Tipo</th>
							<th class="text-end">USD</th>
							<th class="text-end">VES (creación)</th>
						</tr>
					</thead>
					<tbody id="itemsBody">
					@forelse($visibleItems as $it)
						<tr class="item-row"
							data-apto="{{ strtolower($it->apartment->code ?? $it->apartment_id) }}"
							data-concepto="{{ strtolower($it->expenseItem->name ?? '') }}">
							<td>{{ $it->apartment->code ?? ('#'.$it->apartment_id) }}</td>
							<td>{{ $it->expenseItem->name ?? ('Item '.$it->expense_item_id) }}</td>
							<td>{{ (($it->expenseItem->type ?? 'fixed') === 'aliquot') ? 'Alícuota' : 'Fijo' }}</td>
							<td class="text-end">{{ number_format($it->subtotal_usd,2) }}</td>
							<td class="text-end">{{ number_format($it->subtotal_ves,2) }}</td>
						</tr>
					@empty
						<tr><td colspan="5" class="text-center text-muted py-3">Sin ítems</td></tr>
					@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>

	@if(!$invoice->parent_id && ($invoice->children && $invoice->children->count()))
	<div class="card mt-3" id="child-list">
		<div class="card-header d-flex justify-content-between align-items-center">
			<span><i class="bi bi-diagram-3 me-1"></i>Sub-facturas por apartamento</span>
			<span class="text-muted small">{{ $invoice->children->count() }} sub-factura(s)</span>
		</div>
		@if($invoice->children->count() > 10)
		<div class="card-body py-2 border-bottom d-flex align-items-center gap-3 flex-wrap">
			<div class="input-group input-group-sm" style="max-width:220px;">
				<span class="input-group-text"><i class="bi bi-search"></i></span>
				<input type="text" id="childSearch" class="form-control" placeholder="Buscar apartamento...">
			</div>
			<div class="d-flex align-items-center gap-2">
				<label class="form-label mb-0 small text-muted">Mostrar</label>
				<select id="childPerPage" class="form-select form-select-sm" style="width:auto;">
					<option value="10">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="0">Todos</option>
				</select>
			</div>
			<nav id="childPagination" class="ms-auto"></nav>
		</div>
		@endif
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>Número</th>
							<th>Apartamento</th>
							<th class="text-end">Total USD</th>
							<th>Estado</th>
							<th style="width:100px"></th>
						</tr>
					</thead>
					<tbody id="childBody">
							@foreach($invoice->children as $child)
						<tr class="child-row" data-apto="{{ strtolower($child->apartment->code ?? $child->apartment_id) }}">
							<td>{{ $child->number ?? ('#'.$child->id) }}</td>
							<td>{{ $child->apartment->code ?? ('#'.$child->apartment_id) }}</td>
							<td class="text-end">{{ number_format($child->total_usd,2) }}</td>
								@php
									$childBadge = ($child->status === 'paid')
										? 'success'
										: (($child->status === 'pending') ? 'warning' : 'secondary');
								@endphp
							<td><span class="badge bg-{{ $childBadge }}">{{ $child->statusLabel() }}</span></td>
							<td class="text-end"><a href="{{ route('invoices.show',$child) }}" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-eye"></i> Ver</a></td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
	@endif

	@php
		$paymentReports = $invoice->paymentReports ?? collect();
		$approvedPaidUsdEqSummary = 0.0;
		foreach ($paymentReports as $pr) {
			if (($pr->status ?? null) !== 'approved') {
				continue;
			}
			$approvedPaidUsdEqSummary += (float) $pr->usdEquivalent();
		}
		$lateUsdForSummary = (float) $invoice->computeLateFeeUsd();
		$dueUsdForSummary = (float) $myTotalUsd + (float) $lateUsdForSummary;
		$remainingUsdEqSummary = max(0.0, round($dueUsdForSummary - (float) $approvedPaidUsdEqSummary, 2));
	@endphp
	<div class="card mt-3">
		<div class="card-header"><i class="bi bi-cash-coin me-1"></i>Pagos registrados ({{ $paymentReports->count() }})</div>
		<div class="card-body">
			<div class="row g-2 mb-2">
				<div class="col-md-6"><small class="text-muted">Total aprobado (USD equiv)</small><div><strong>{{ number_format($approvedPaidUsdEqSummary,2) }}</strong></div></div>
				<div class="col-md-6"><small class="text-muted">Saldo pendiente (USD equiv)</small><div><strong>{{ number_format($remainingUsdEqSummary,2) }}</strong></div></div>
			</div>
			@if($paymentReports->count() === 0)
				<div class="text-muted">Sin pagos registrados.</div>
			@else
				<div class="table-responsive">
					<table class="table table-hover table-sm align-middle mb-0">
						<thead>
							<tr>
								<th>#</th>
								<th>Fecha</th>
								<th>Estado</th>
								<th class="text-end">USD equiv</th>
								<th class="text-end">USD</th>
								<th class="text-end">VES</th>
								<th class="text-end">Tasa</th>
								<th style="width:220px"></th>
							</tr>
						</thead>
						<tbody>
							@foreach($paymentReports as $pr)
								@php
									$prBadge = ($pr->status === 'approved') ? 'success' : (($pr->status === 'rejected') ? 'danger' : 'secondary');
									$rowClass = ($pr->status === 'approved') ? 'table-success' : (($pr->status === 'rejected') ? 'table-danger' : '');
								@endphp
								<tr class="{{ $rowClass }}">
									<td>#{{ $pr->id }}</td>
									<td>{{ $pr->created_at ? $pr->created_at->format('Y-m-d H:i') : '' }}</td>
									<td><span class="badge bg-{{ $prBadge }}">{{ $pr->statusLabel() }}</span></td>
									<td class="text-end">{{ number_format((float)$pr->usdEquivalent(), 2) }}</td>
									<td class="text-end">{{ number_format((float)$pr->amount_usd, 2) }}</td>
									<td class="text-end">{{ number_format((float)$pr->amount_ves, 2) }}</td>
									<td class="text-end">{{ number_format((float)$pr->exchange_rate_used, 2) }}</td>
									<td class="text-end">
										<div class="d-flex justify-content-end gap-1" role="group" aria-label="Acciones de pago">
											@can('approve', $pr)
												<a class="btn btn-sm btn-outline-primary" href="{{ route('payments.review', $pr) }}">Revisar</a>
												@if(($pr->status ?? null) === 'reported')
													<form method="POST" action="{{ route('payments.approve', $pr) }}">@csrf @method('PATCH')
														<button class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar este abono?')">Aprobar</button>
													</form>
													@can('reject', $pr)
														<form method="POST" action="{{ route('payments.reject', $pr) }}">@csrf @method('PATCH')
															<button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Rechazar este abono?')">Rechazar</button>
														</form>
													@endcan
												@endif
											@endcan
										</div>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			@endif
		</div>
	</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
	// Reusable client-side paginator
	function initPaginator(cfg){
		const rows = Array.from(document.querySelectorAll(cfg.rowSelector));
		if(rows.length <= 10 && !cfg.forceInit) return;
		const searchInput = document.getElementById(cfg.searchId);
		const perPageSelect = document.getElementById(cfg.perPageId);
		const paginationNav = document.getElementById(cfg.paginationId);
		if(!searchInput || !perPageSelect || !paginationNav) return;
		let currentPage = 1;

		function getFiltered(){
			const q = (searchInput.value || '').toLowerCase();
			if(!q) return rows;
			return rows.filter(r => {
				return (cfg.searchFields || []).some(f => (r.dataset[f]||'').includes(q));
			});
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
	}

	// Items table
	initPaginator({
		rowSelector: '#itemsBody .item-row',
		searchId: 'itemSearch',
		perPageId: 'itemsPerPage',
		paginationId: 'itemsPagination',
		searchFields: ['apto','concepto']
	});

	// Sub-facturas table
	initPaginator({
		rowSelector: '#childBody .child-row',
		searchId: 'childSearch',
		perPageId: 'childPerPage',
		paginationId: 'childPagination',
		searchFields: ['apto']
	});
});
</script>
@endpush

@endsection

