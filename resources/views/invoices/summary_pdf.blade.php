<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body{font-family:DejaVu Sans,sans-serif;font-size:11px;margin:28px;color:#222}
		.header{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #444;padding-bottom:8px;margin-bottom:12px}
		.title{font-size:16px;margin:0}
		.subtitle{font-size:11px;color:#666;margin:2px 0 0 0}
		table{width:100%;border-collapse:collapse;margin-top:10px}
		th,td{border:1px solid #bbb;padding:4px 5px;text-align:left;font-size:10px}
		th{background:#efefef}
		tbody tr:nth-child(odd){background:#fafafa}
		.right{text-align:right}
		.totals{margin-top:12px;border-top:2px solid #444;padding-top:8px}
		.totals p{margin:3px 0}
		.badge{padding:2px 6px;border-radius:3px;font-size:9px}
		.badge-paid{background:#d4edda;color:#155724}
		.badge-pending{background:#fff3cd;color:#856404}
		.badge-draft{background:#e2e3e5;color:#383d41}
	</style>
</head>
<body>
	<div class="header">
		<div>
			<div class="title">Resumen de Facturación</div>
			<div class="subtitle">Período: {{ $period }}</div>
		</div>
		<div style="text-align:right">
			<div class="subtitle">Generado: {{ now()->format('d/m/Y H:i') }}</div>
			<div class="subtitle">{{ $invoices->count() }} facturas</div>
		</div>
	</div>

	<table>
		<thead>
			<tr>
				<th style="width:110px">Factura</th>
				<th>Torre</th>
				<th>Apto</th>
				<th>Estado</th>
				<th>Vence</th>
				<th class="right">USD</th>
				<th class="right">VES</th>
			</tr>
		</thead>
		<tbody>
			@foreach($invoices as $inv)
				<tr>
					<td>{{ $inv->number }}</td>
					<td>{{ $inv->apartment?->tower?->name ?? '—' }}</td>
					<td>{{ $inv->apartment?->code ?? '—' }}</td>
					<td>{{ $inv->statusLabel() }}</td>
					<td>{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
					<td class="right">{{ number_format($inv->total_usd, 2) }}</td>
					<td class="right">{{ number_format($inv->total_ves, 2) }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>

	<div class="totals">
		<p><strong>Total facturado:</strong> $ {{ number_format($totalUsd, 2) }} USD / {{ number_format($totalVes, 2) }} VES</p>
		<p><strong>Cobrado:</strong> $ {{ number_format($totalPaid, 2) }} USD</p>
		<p><strong>Pendiente:</strong> $ {{ number_format($totalPending, 2) }} USD</p>
	</div>
</body>
</html>