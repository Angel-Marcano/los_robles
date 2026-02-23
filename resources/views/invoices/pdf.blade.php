<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body{font-family:DejaVu Sans, sans-serif;font-size:12px;margin:28px;color:#222}
		.header{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #444;padding-bottom:8px;margin-bottom:10px}
		.brand .title{font-size:18px;margin:0;padding:0}
		.brand .subtitle{font-size:12px;color:#666;margin:2px 0 0 0}
		.doc h2{margin:0;font-size:16px}
		.meta{margin:8px 0 0 0;font-size:11px;color:#444}
		table{width:100%;border-collapse:collapse;margin-top:10px}
		th,td{border:1px solid #bbb;padding:6px 6px;text-align:left;font-size:11px}
		th{background:#efefef}
		tbody tr:nth-child(odd){background:#fafafa}
		.right{text-align:right}
		.totals{margin-top:12px;border-top:2px solid #444;padding-top:8px}
		.totals p{margin:3px 0}
		.small{font-size:10px;color:#666;margin-top:12px}
		.watermark{position:fixed;top:40%;left:15%;font-size:64px;color:rgba(0,0,0,0.06);transform:rotate(-25deg)}
		.watermark2{position:fixed;top:65%;left:10%;font-size:64px;color:rgba(0,0,0,0.06);transform:rotate(-25deg)}
		.watermark3{position:fixed;top:18%;left:20%;font-size:64px;color:rgba(0,0,0,0.06);transform:rotate(-25deg)}
		.muted{color:#666}
		.empty{padding:16px;border:1px dashed #bbb;background:#fcfcfc;margin-top:8px}
	</style>
</head>
<body>
@php
	$condo = (app()->bound('currentCondominium') && app('currentCondominium')) ? app('currentCondominium') : null;
	$dynamicLateUsd = (float) $invoice->computeLateFeeUsd();
	$dynamicLateVes = (float) $invoice->computeLateFeeVes();
	$items = isset($items) ? $items : $invoice->items;
	$myTotalUsd = round((float) $items->sum('subtotal_usd'), 2);
	$myTotalVes = round((float) $items->sum('subtotal_ves'), 2);
	$share = ((float) $invoice->total_usd) > 0 ? ($myTotalUsd / (float) $invoice->total_usd) : 0.0;
	$lateUsd = $invoice->status === 'paid'
		? (float) $invoice->late_fee_accrued_usd * $share
		: $dynamicLateUsd * $share;
	$lateVes = $invoice->status === 'paid'
		? (float) $invoice->late_fee_accrued_ves * $share
		: $dynamicLateVes * $share;
	$grandUsd = $myTotalUsd + $lateUsd;
	$grandVes = $myTotalVes + $lateVes;
	$isChild = !is_null($invoice->parent_id);
@endphp
@if($invoice->status==='paid')
	<div class="watermark">PAGADO</div>
	<div class="watermark2">PAGADO</div>
	<div class="watermark3">PAGADO</div>
@endif

<div class="header">
	<div class="brand">
		<p class="title">{{ $condo ? $condo->name : 'Condominio' }}</p>
		<p class="subtitle">
			Factura {{ $invoice->number ?? ('#'.$invoice->id) }} — Periodo {{$invoice->period}}
			@if($isChild && $invoice->apartment)
				— Apartamento: {{ $invoice->apartment->code }}
				@if($invoice->late_fee_scope)
					— {{ $invoice->lateFeeLabel() }}
				@endif
			@endif
		</p>
	</div>
	<div class="doc">
		<h2>Total USD {{ number_format($grandUsd,2) }}</h2>
		<div class="meta">Estado: {{$invoice->statusLabel()}} | Vence: {{$invoice->due_date? $invoice->due_date->format('Y-m-d'):'--'}}<br/>Tasa usada: {{$invoice->exchange_rate_used}} @if($invoice->tower) | Torre: {{$invoice->tower->name}} @endif</div>
	</div>
    
    
    
    
</div>

@if($items && $items->count())
<table>
	<thead>
		<tr>
			<th style="width:32px">#</th>
			@if(!$isChild)
				<th style="width:120px">Apartamento</th>
			@endif
			<th>Concepto</th>
			<th style="width:90px">Tipo</th>
			<th style="width:90px" class="right">USD</th>
			<th style="width:100px" class="right">VES</th>
		</tr>
	</thead>
	<tbody>
	@foreach($items as $idx=>$it)
		<tr>
			<td>{{$idx+1}}</td>
			@if(!$isChild)
				<td>{{ $it->apartment->code ?? ('Apto #'.$it->apartment_id) }}</td>
			@endif
			<td>{{ $it->expenseItem->name ?? ('Item '.$it->expense_item_id) }}</td>
			<td>{{ (($it->expenseItem->type ?? 'fixed') === 'aliquot') ? 'Alícuota' : 'Fijo' }}</td>
			<td class="right">{{ number_format($it->subtotal_usd,2) }}</td>
			<td class="right">{{ number_format($it->subtotal_ves,2) }}</td>
		</tr>
	@endforeach
	</tbody>
	</table>
@else
	<div class="empty">Esta factura no posee ítems.</div>
@endif

<div class="totals">
	<p>Subtotal USD: <strong>{{ number_format($myTotalUsd,2) }}</strong> — VES: <strong>{{ number_format($myTotalVes,2) }}</strong></p>
	<p>Mora @if($invoice->status==='paid')(fijada)@else(dinámica)@endif — USD: <strong>{{ number_format($lateUsd,2) }}</strong> — VES: <strong>{{ number_format($lateVes,2) }}</strong></p>

	@php
		$allPayments = $invoice->relationLoaded('paymentReports')
			? ($invoice->paymentReports ?? collect())
			: $invoice->paymentReports()->get();

		$paidUsdEq = 0.0;
		foreach ($allPayments as $pr) {
			if (($pr->status ?? null) !== 'approved') {
				continue;
			}
			$paidUsdEq += (float) $pr->usdEquivalent();
		}
		$totalDueUsdEq = (float) $grandUsd;
		$remainingUsdEq = max(0.0, round($totalDueUsdEq - (float)$paidUsdEq, 2));
	@endphp
	@php($totalDueUsdEq = isset($totalDueUsdEq) ? (float)$totalDueUsdEq : (float)$grandUsd)
	@php($remainingUsdEq = isset($remainingUsdEq) ? (float)$remainingUsdEq : max(0.0, round($totalDueUsdEq - (float)$paidUsdEq, 2)))
	@if($invoice->status !== 'paid')
		<p style="margin:0">
			Total a pagar (USD equivalente): <strong>{{ number_format($totalDueUsdEq,2) }}</strong> —
			Pagado aprobado: <strong>{{ number_format((float)$paidUsdEq,2) }}</strong> —
			Saldo pendiente: <strong>{{ number_format($remainingUsdEq,2) }}</strong>
		</p>
	@endif
	@if($allPayments->count() > 0)
		<hr/>
		<h3 style="margin:6px 0 4px">Pagos registrados</h3>
		<table class="table">
			<thead>
				<tr>
					<th style="width:80px">#</th>
					<th style="width:120px">Fecha</th>
					<th style="width:90px">Estado</th>
					<th style="width:90px">USD equiv</th>
					<th style="width:80px">USD</th>
					<th style="width:110px">VES</th>
					<th style="width:100px">Tasa</th>
				</tr>
			</thead>
			<tbody>
				@foreach($allPayments as $pr)
					<tr>
						<td>#{{ $pr->id }}</td>
						<td>{{ $pr->created_at ? $pr->created_at->format('Y-m-d') : '' }}</td>
						<td>{{ $pr->statusLabel() }}</td>
						<td class="right">{{ number_format((float)$pr->usdEquivalent(),2) }}</td>
						<td class="right">{{ number_format((float)$pr->amount_usd,2) }}</td>
						<td class="right">{{ number_format((float)$pr->amount_ves,2) }}</td>
						<td class="right">{{ number_format((float)$pr->exchange_rate_used,6) }}</td>
					</tr>
				@endforeach
			</tbody>
		</table>
		<p style="margin:0">Total pagado (aprobado, USD equivalente): <strong>{{ number_format((float)$paidUsdEq,2) }}</strong></p>
	@endif
	<p>Total + Mora USD: <strong>{{ number_format($grandUsd,2) }}</strong> — VES: <strong>{{ number_format($grandVes,2) }}</strong></p>
	<p class="muted small">Generado el {{ now()->format('Y-m-d H:i') }}. Si la factura no está pagada, la mora se recalcula según días de atraso.</p>
    
</div>

</body>
</html>
