@component('mail::message')
# Recordatorio de pago

Hola {{ $invoice->owner_name ?? 'propietario' }},

Te recordamos que la factura **{{ $invoice->number }}** del período **{{ $invoice->period }}** se encuentra vencida.

- **Monto original:** {{ number_format($invoice->total_usd, 2) }} USD / {{ number_format($invoice->total_ves, 2) }} VES
- **Mora acumulada:** {{ number_format($lateUsd, 2) }} USD / {{ number_format($lateVes, 2) }} VES
- **Total a pagar:** {{ number_format($invoice->total_usd + $lateUsd, 2) }} USD / {{ number_format($invoice->total_ves + $lateVes, 2) }} VES
- **Fecha de vencimiento:** {{ optional($invoice->due_date)->format('d/m/Y') }}

Por favor regulariza tu pago a la brevedad. Si ya pagaste, ignora este mensaje.

@component('mail::button', ['url' => route('invoices.show', $invoice)])
Ver factura
@endcomponent

Gracias,
{{ config('app.name') }}
@endcomponent
