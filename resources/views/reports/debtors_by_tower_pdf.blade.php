<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Morosidad por Torre {{ $year }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; margin: 20px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #f0f0f0; text-align: left; padding: 4px 6px; font-size: 10px; }
        td { padding: 3px 6px; border-bottom: 1px solid #eee; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; background: #f8f8f8; }
        .grand-total { font-size: 14px; font-weight: bold; margin-top: 15px; text-align: right; }
    </style>
</head>
<body>
    <h1>Morosidad por Torre — {{ $year }}</h1>
    <p style="color:#666; font-size:10px;">Generado: {{ now()->format('d/m/Y H:i') }}</p>

    @php $grandTotal = 0; @endphp
    @foreach($rows as $tower)
    @php $grandTotal += $tower['tower_total']; @endphp
    <h2>{{ $tower['tower_name'] }} — Total: {{ number_format($tower['tower_total'], 2) }} USD</h2>
    <table>
        <thead>
            <tr>
                <th>Apartamento</th>
                <th>Propietario</th>
                <th class="text-end">Saldo Pendiente USD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tower['apartments'] as $apt)
            <tr>
                <td>{{ $apt['apartment_code'] }}</td>
                <td>{{ $apt['owner_name'] ?: '—' }}</td>
                <td class="text-end">{{ number_format($apt['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-end">Total {{ $tower['tower_name'] }}</td>
                <td class="text-end">{{ number_format($tower['tower_total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endforeach

    <div class="grand-total">Gran Total: {{ number_format($grandTotal, 2) }} USD</div>
</body>
</html>