# Módulo: Cobros / Reportes de Pago

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Los propietarios/residentes **reportan pagos** (abonos) sobre sus sub-facturas. Los
administradores **revisan, aprueban o rechazan** esos reportes. Al aprobar, se acumula el
pago y, si cubre el total + mora, la factura se marca `paid` y se acredita el fondo de
reserva. Soporta adjuntos (comprobantes) y conversión USD/VES.

## Modelo

### `App\Models\PaymentReport` (BD tenant, SoftDeletes)
- **Fillable**: `invoice_id`, `user_id`, `apartment_id`, `reported_by`, `payment_method`,
  `reference_number`, `paid_at`, `amount_usd`, `amount_ves`, `exchange_rate_used`,
  `exchange_rate_valid_from`, `currency_rate_id`, `usd_equivalent`, `status`, `files` (array), `notes`.
- **Casts**: `amount_usd`, `amount_ves`, `usd_equivalent` (decimal:2),
  `exchange_rate_valid_from` (datetime), `files` (array).
- **Status**: `reported` → `approved` | `rejected`.
- **Relaciones**: `invoice()` → belongsTo `Invoice`; `currencyRate()` → belongsTo `CurrencyRate`.
- **Métodos**:
  - `statusLabel()` — Reportado / Aprobado / Rechazado.
  - `usdEquivalent()` — usa `usd_equivalent` si no es null; si no, calcula
    `amount_usd + (amount_ves / exchange_rate_used)`.

## Servicios

### `App\Services\PaymentAttachmentStorageService`
- `activeDisk()` — `public` por defecto; si es `s3` y no hay config, cae a fallback local.
- `storeForInvoice(UploadedFile, Invoice)` — guarda en `payment-attachments/{condo}/{invoice}/`.
- `buildReviewLinks(array $paths)` — resuelve URLs para revisión (soporta histórico).
- `resolveUrl($path)` — URL según disco (public/s3).

### `App\Services\ReserveFundService` (ver `fondo_reserva.md`)
- `creditFromPaidInvoice(Invoice)` — al marcar factura `paid`, acredita al fondo de la torre
  la fracción de reserva proporcional al pago aprobado (USD y VES).

## Controlador: `PaymentReportController`

### `create(Invoice)`
- Solo si `invoice.status === 'pending'`.
- Bloquea reportar sobre factura padre con hijas (debe hacerse sobre la sub-factura).
- Calcula sugerencias: `lateUsd`, `dueUsd`, `paidUsdEquivalent`, `reportedUsdEquivalent`,
  `remainingUsd`, `suggestedUsdToReport` y equivalentes en VES con tasa activa.

### `store(Request, Invoice, PaymentAttachmentStorageService)`
- Valida: `amount_usd`, `amount_ves` (nullable, min 0), `files.*` (jpg/png/pdf, max 4MB),
  `notes` (nullable).
- Requiere al menos un monto > 0. Si hay VES, requiere tasa activa.
- **Validación de saldo**: el abono no puede exceder
  `dueUsd - (approved + reported)` (tolerancia 0.005).
- Calcula `usd_equivalent` y guarda con `status='reported'`.
- Audita `payment_report_created`.

### `review(PaymentReport, PaymentAttachmentStorageService)`
- Muestra detalle + links de comprobantes.

### `approve(PaymentReport)`
- Solo admins (ver policy). Validaciones:
  - Status debe ser `reported`.
  - Si hay VES, debe tener `exchange_rate_used > 0`.
  - No aprobar si la factura ya está `paid`.
  - No exceder saldo pendiente (`dueUsd - approved`).
  - Si es padre con hijas pendientes, bloquear (aprobar sobre la sub-factura).
- En transacción:
  - Marca `status='approved'`, backfill `usd_equivalent` si era null.
  - Si `approvedPaidUsdEquivalent >= dueUsd` → marca factura `paid` con
    `paid_exchange_rate`, `late_fee_accrued_*`.
  - Llama `ReserveFundService::creditFromPaidInvoice()`.
  - Si es sub-factura y todas las hermanas pagadas → marca al padre `paid`.
- Audita `payment_report_approved`.

### `reject(PaymentReport)`
- Marca `status='rejected'`. Audita `payment_report_rejected`.

## Observador: `PaymentReportObserver`
- `created` → audita `payment_report_created_observer`.
- `updated` → audita `payment_report_updated`.

## Policy: `PaymentReportPolicy`
- `create` → admins + `owner` + `tenant`.
- `approve` / `reject`:
  - `super_admin` → siempre.
  - Si `invoice.tower_id` → solo `tower_admin`.
  - Si no (factura de condominio) → solo `condo_admin`.

## Rutas (anidadas en `invoices`, middleware `auth`)
- `payments.create` → `GET invoices/{invoice}/payments/create`
- `payments.store` → `POST invoices/{invoice}/payments`
- `review` / `approve` / `reject` (rutas adicionales, ver controlador).

## Reglas de negocio clave

1. **Solo se reporta pago sobre sub-facturas `pending`** (no padre, no draft/paid/voided).
2. **Un reporte es un abono**: pueden existir varios reportes aprobados que suman hasta
   cubrir el total + mora.
3. **Validación de no exceder saldo** tanto al reportar como al aprobar.
4. **Aprobación dispara**: marca `paid` si cubre, acredita fondo de reserva, propaga al padre.
5. **Comprobantes** se almacenan en disco configurable (public/s3) con fallback.
6. **Tasa**: el VES se convierte con `exchange_rate_used` capturado al reportar.

## Vistas
- `resources/views/payments/` — create, review.

## Casos de uso típicos
- Propietario reporta un abono parcial (USD + VES) con comprobante.
- Admin revisa comprobante, aprueba → factura se marca pagada si cubre.
- Admin rechaza un reporte con monto inconsistente.
- Aprobación de último abono dismarca el padre como pagado automáticamente.

## Notas / deudas
- Compatibilidad: reportes viejos sin `status` se cuentan como aprobados en reportes.
- Validar que `payment_method` y `reference_number` sean obligatorios (hoy nullable).