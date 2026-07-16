@extends('layouts.app')

@section('content')
<div class="row justify-content-center" style="min-height: 70vh; align-items:center;">
    <div class="col-md-8 col-lg-7">
        <div class="card">
            <div class="card-body p-4 p-lg-5 text-center">
                @if($status === 'valida')
                    <div class="mb-3 text-success" style="font-size: 3rem;"><i class="bi bi-patch-check-fill"></i></div>
                    <h1 class="h4">Factura valida</h1>
                    <p class="text-muted mb-4">La firma criptografica coincide con los datos actuales de la factura.</p>
                    <dl class="row text-start mb-0">
                        <dt class="col-sm-4">Factura</dt>
                        <dd class="col-sm-8">{{ $invoice->number ?? ('#'.$invoice->id) }}</dd>
                        <dt class="col-sm-4">Periodo</dt>
                        <dd class="col-sm-8">{{ $invoice->period }}</dd>
                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">{{ $invoice->statusLabel() }}</dd>
                        <dt class="col-sm-4">Firmada</dt>
                        <dd class="col-sm-8">{{ $invoice->signed_at ? $invoice->signed_at->format('Y-m-d H:i') : 'N/D' }}</dd>
                    </dl>
                @elseif($status === 'anulada')
                    <div class="mb-3 text-warning" style="font-size: 3rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h1 class="h4">Factura anulada o invalida</h1>
                    <p class="text-muted mb-0">La factura fue anulada o su firma no coincide con los datos vigentes.</p>
                @else
                    <div class="mb-3 text-danger" style="font-size: 3rem;"><i class="bi bi-x-octagon-fill"></i></div>
                    <h1 class="h4">Factura no existe</h1>
                    <p class="text-muted mb-0">No se encontro una factura valida para este token de verificacion.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
