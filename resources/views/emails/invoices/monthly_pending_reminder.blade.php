@component('mail::message')
# Recordatorio de factura pendiente

Hola {{ $invoice->owner_name ?? 'propietario' }},

Te recordamos que tienes una factura **pendiente** correspondiente al período **{{ $invoice->period }}**.

**Detalles:**
- Número: {{ $invoice->number }}
- Monto: {{ number_format($invoice->total_usd, 2) }} USD / {{ number_format($invoice->total_ves, 2) }} VES
- Fecha de vencimiento: {{ optional($invoice->due_date)->format('d/m/Y') }}

Por favor realiza el pago antes de la fecha de vencimiento para evitar moras.

@component('mail::button', ['url' => route('invoices.show', $invoice)])
Ver factura
@endcomponent

Gracias,
{{ app()->bound('currentCondominium') ? app('currentCondominium')->name : config('app.name', 'Los Robles') }}
@endcomponent
