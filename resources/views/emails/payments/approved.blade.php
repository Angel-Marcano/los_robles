@component('mail::message')
# Pago aprobado

Hola {{ $invoice->owner_name ?? 'propietario' }},

Tu reporte de pago para la factura **{{ $invoice->number }}** del período **{{ $invoice->period }}** ha sido **aprobado**.

**Detalles del pago:**
- Monto USD: {{ number_format($paymentReport->amount_usd, 2) }}
- Monto VES: {{ number_format($paymentReport->amount_ves, 2) }}
- Fecha de aprobación: {{ now()->format('d/m/Y') }}

**Estado de la factura:**
- Total: {{ number_format($invoice->total_usd, 2) }} USD
- Saldo pendiente: {{ number_format($remainingUsd, 2) }} USD

@if($remainingUsd <= 0)
🎉 ¡La factura ha sido pagada en su totalidad!
@else
Por favor regulariza el saldo pendiente a la brevedad.
@endif

@component('mail::button', ['url' => route('invoices.show', $invoice)])
Ver factura
@endcomponent

Gracias,
{{ app()->bound('currentCondominium') ? app('currentCondominium')->name : config('app.name', 'Los Robles') }}
@endcomponent
