@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
    <div>
        <h1><i class="bi bi-exclamation-triangle me-2"></i>Morosidad por Torre</h1>
        <p class="text-muted mb-0">Saldo pendiente agrupado por torre y apartamento ({{ $year }}).</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex align-items-center gap-2">
            <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" class="form-control form-control-sm" style="width:90px">
            <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-arrow-clockwise"></i></button>
        </form>
        <a href="{{ route('reports.debtorsByTowerCsv', ['year' => $year]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <a href="{{ route('reports.debtorsByTowerPdf', ['year' => $year]) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
    </div>
</div>

@if(empty($rows))
    <div class="empty-state">
        <i class="bi bi-check-circle"></i>
        <p>No hay morosidad registrada para {{ $year }}.</p>
    </div>
@else
@foreach($rows as $tower)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-building me-1"></i>{{ $tower['tower_name'] }}</h5>
        <span class="badge bg-danger-subtle text-danger-emphasis fs-6">Total: {{ number_format($tower['tower_total'], 2) }} USD</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
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
                    <td class="fw-semibold">{{ $apt['apartment_code'] }}</td>
                    <td>{{ $apt['owner_name'] ?: '—' }}</td>
                    <td class="text-end font-monospace">{{ number_format($apt['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="2" class="text-end">Total {{ $tower['tower_name'] }}</td>
                    <td class="text-end font-monospace">{{ number_format($tower['tower_total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach
@endif
@endsection