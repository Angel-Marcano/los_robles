@extends('layouts.app')
@section('content')
<div class="container py-3">
	@if(session('status'))
		<div class="alert alert-success">{{ session('status') }}</div>
	@endif

	@if($errors->any())
		<div class="alert alert-danger">
			<ul class="mb-0">
				@foreach($errors->all() as $e)
					<li>{{ $e }}</li>
			$approvedPaidUsdEq += (float) $pr->usdEquivalent();
	@php
		$currentRate = \App\Models\CurrencyRate::where('active', true)->orderByDesc('valid_from')->first();
		$lateUsd = $invoice->computeLateFeeUsd();
		$effectiveRate = $currentRate->rate ?? $invoice->exchange_rate_used;
		$visibleItems = isset($items) ? $items : $invoice->items;
		$myTotalUsd = round($visibleItems->sum('subtotal_usd'), 2);
		$myTotalVes = round($visibleItems->sum('subtotal_ves'), 2);
		$totalVesVar = number_format($myTotalUsd * $effectiveRate, 2);
	@endphp

	<div class="d-flex align-items-center justify-content-between mb-3">
		<div>
			<h2 class="h4 mb-1">Factura #{{ $invoice->id }}</h2>
			<div class="text-muted">Periodo: {{ $invoice->period }} | Vence: {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/D' }}</div>
		</div>
		<div>
			@php
				$badgeClass = ($invoice->status === 'paid')
					? 'success'
					: (($invoice->status === 'pending')
						? 'warning'
						: (($invoice->status === 'draft') ? 'secondary' : 'secondary'));
			@endphp
			<span class="badge bg-{{ $badgeClass }}">{{ $invoice->statusLabel() }}</span>
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
				</div>
			@endif
			<div class="row g-3">
				<div class="col-12 col-md-6">
					<div class="d-flex justify-content-between"><span class="text-muted">Total USD:</span><strong>{{ number_format($myTotalUsd,2) }}</strong></div>
					<div class="d-flex justify-content-between"><span class="text-muted">Total VES:</span>
						<strong>
							@if($invoice->status==='paid')
								{{ number_format($myTotalVes,2) }} <span class="text-muted">(tasa pagada {{ $invoice->paid_exchange_rate }})</span>
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

	<div class="d-flex gap-2 mb-3">
		<a class="btn btn-outline-primary" href="{{ route('invoices.pdf',$invoice) }}" target="_blank">Ver PDF</a>
			@if($invoice->status==='draft')
				<a class="btn btn-primary" href="{{ route('invoices.edit',$invoice) }}">Editar</a>
			@endif
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
			<a class="btn btn-info" href="{{ route('payments.create',$invoice) }}">Registrar abono</a>
		@endif
	</div>

	<div class="card">
		<div class="card-header">Detalle de ítems</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Apto</th>
							<th>Concepto</th>
							<th>Tipo</th>
							<th class="text-end">USD</th>
							<th class="text-end">VES (creación)</th>
						</tr>
					</thead>
					<tbody>
					@forelse($visibleItems as $it)
						<tr>
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
		<div class="card-header">Sub-facturas por apartamento</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Número</th>
							<th>Apartamento</th>
							<th class="text-end">Total USD</th>
							<th>Estado</th>
							<th style="width:100px"></th>
						</tr>
					</thead>
					<tbody>
							@foreach($invoice->children as $child)
						<tr>
							<td>{{ $child->number ?? ('#'.$child->id) }}</td>
							<td>{{ $child->apartment->code ?? ('#'.$child->apartment_id) }}</td>
							<td class="text-end">{{ number_format($child->total_usd,2) }}</td>
								@php
									$childBadge = ($child->status === 'paid')
										? 'success'
										: (($child->status === 'pending') ? 'warning' : 'secondary');
								@endphp
							<td><span class="badge bg-{{ $childBadge }}">{{ $child->statusLabel() }}</span></td>
							<td class="text-end"><a href="{{ route('invoices.show',$child) }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
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
		<div class="card-header">Pagos registrados ({{ $paymentReports->count() }})</div>
		<div class="card-body">
			<div class="row g-2 mb-2">
				<div class="col-md-6"><small class="text-muted">Total aprobado (USD equiv)</small><div><strong>{{ number_format($approvedPaidUsdEqSummary,2) }}</strong></div></div>
				<div class="col-md-6"><small class="text-muted">Saldo pendiente (USD equiv)</small><div><strong>{{ number_format($remainingUsdEqSummary,2) }}</strong></div></div>
			</div>
			@if($paymentReports->count() === 0)
				<div class="text-muted">Sin pagos registrados.</div>
			@else
				<div class="table-responsive">
					<table class="table table-sm table-striped mb-0">
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
									<td class="text-end">{{ number_format((float)$pr->exchange_rate_used, 6) }}</td>
									<td class="text-end">
										<div class="btn-group" role="group" aria-label="Acciones de pago">
											@can('approve', $pr)
												<a class="btn btn-sm btn-outline-primary" href="{{ route('payments.review', $pr) }}">Revisar</a>
												@if(($pr->status ?? null) === 'reported')
													<form method="POST" action="{{ route('payments.approve', $pr) }}" style="display:inline">@csrf @method('PATCH')
														<button class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar este abono?')">Aprobar</button>
													</form>
													@can('reject', $pr)
														<form method="POST" action="{{ route('payments.reject', $pr) }}" style="display:inline">@csrf @method('PATCH')
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

</div>
@endsection

