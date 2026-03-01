<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 15mm 10mm; color: #222; }
		h1 { font-size: 16px; margin: 0 0 2px 0; }
		.subtitle { font-size: 11px; color: #666; margin: 0 0 10px 0; }
		.meta { font-size: 9px; color: #888; margin-bottom: 10px; }
		table { width: 100%; border-collapse: collapse; margin-top: 6px; }
		th, td { border: 1px solid #bbb; padding: 4px 5px; font-size: 9px; }
		th { background: #efefef; font-weight: bold; }
		tbody tr:nth-child(odd) { background: #fafafa; }
		.right { text-align: right; }
		.center { text-align: center; }
		.debt { color: #c00; font-weight: 600; }
		.footer { font-size: 8px; color: #999; margin-top: 14px; text-align: center; }
		.brand { border-bottom: 2px solid #444; padding-bottom: 6px; margin-bottom: 8px; }
	</style>
</head>
<body>
@php
	$condo = (app()->bound('currentCondominium') && app('currentCondominium')) ? app('currentCondominium') : null;
	$monthNames = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];
@endphp

<div class="brand">
	<h1>{{ $condo ? $condo->name : config('app.name', 'Los Robles') }}</h1>
	<p class="subtitle">Reporte de Deudores por Mes &mdash; {{ $year }}</p>
</div>

<div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</div>

@if(count($rows) === 0)
	<p style="padding: 20px; text-align: center; color: #888;">Sin deudores pendientes para {{ $year }}</p>
@else
	<table>
		<thead>
			<tr>
				<th style="min-width: 80px;">Deudor</th>
				@foreach($months as $m => $label)
					<th class="right">{{ $label }}</th>
				@endforeach
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			@foreach($rows as $r)
				<tr>
					<td>
						<strong>{{ $r['apartment_code'] }}</strong>
						@if(!empty($r['tower_name']))
							<span style="color:#888; font-size:8px;">({{ $r['tower_name'] }})</span>
						@endif
						@if(!empty($r['owner_name']))
							<br><span style="color:#666; font-size:8px;">{{ $r['owner_name'] }}</span>
						@endif
					</td>
					@foreach($months as $m => $label)
						@php($val = (float)($r['monthly'][$m] ?? 0))
						<td class="right {{ $val > 0 ? 'debt' : '' }}">{{ $val > 0 ? number_format($val, 2) : '' }}</td>
					@endforeach
					<td class="right"><strong class="debt">{{ number_format((float)$r['total'], 2) }}</strong></td>
				</tr>
			@endforeach
		</tbody>
		<tfoot>
			<tr style="font-weight: bold; background: #e8e8e8;">
				<td>Total general</td>
				@foreach($months as $m => $label)
					@php($colTotal = collect($rows)->sum(fn($r) => (float)($r['monthly'][$m] ?? 0)))
					<td class="right">{{ $colTotal > 0 ? number_format($colTotal, 2) : '' }}</td>
				@endforeach
				<td class="right">{{ number_format(collect($rows)->sum('total'), 2) }}</td>
			</tr>
		</tfoot>
	</table>
@endif

<div class="footer">
	{{ $condo ? $condo->name : config('app.name', 'Los Robles') }} &bull; Montos en USD equivalente &bull; Página 1
</div>
</body>
</html>
