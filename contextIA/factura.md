# Módulo: Facturación (Facturas)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Núcleo financiero del condominio. Genera **facturas padre** (borrador) que al aprobarse se
**dividen en sub-facturas por apartamento** (hijas). Cada sub-factura es la que ve y paga
el propietario. Maneja mora, anulación, reemisión, firma HMAC y PDF con QR de verificación.

## Modelos

### `App\Models\Invoice` (BD tenant, SoftDeletes)
- **Fillable**: `number`, `correlative`, `parent_id`, `apartment_id`, `tower_id`,
  `created_by`, `period` (YYYY-MM), `due_date` (date), `status`, `late_fee_type`,
  `late_fee_scope`, `late_fee_value`, `late_fee_accrued_usd`, `late_fee_accrued_ves`,
  `exchange_rate_used`, `total_usd`, `total_ves`, `paid_at`, `paid_exchange_rate`,
  `invoice_signature`, `signed_at`, `voided_at`, `void_reason`, `reissued_by`,
  `reissued_to_invoice_id`, `reissued_from_invoice_id`, `reminder_sent_at`,
  `owner_name`, `owner_email`, `owner_document`.
- **Status**: `draft` → `pending` → `paid` | `voided` | `reissued`.
- **Mora**: `late_fee_type` (`percent`|`fixed`), `late_fee_scope` (`day`|`week`|`month`),
  `late_fee_value`. Se cobra por **periodos completos** (week cobra 1 a partir del día 7).
- **Relaciones**:
  - `items()` → hasMany `InvoiceItem`
  - `tower()`, `apartment()`, `creator()`, `parent()`, `children()` (self, parent_id)
  - `paymentReports()` → hasMany `PaymentReport`
  - `reissuedTo()`, `reissuedFrom()` (self-referencia)
- **Métodos clave**:
  - `computeLateFeeUsd()` — mora acumulada; si `paid`, retorna `late_fee_accrued_usd`.
  - `computeLateFeeVes()` — mora en VES usando `exchange_rate_used`.
  - `dueUsdEquivalent()` = `total_usd + computeLateFeeUsd()`.
  - `approvedPaidUsdEquivalent()` — suma USD eq. de `PaymentReport` aprobados.
  - `reportedPaidUsdEquivalent()` — suma USD eq. de reportes en estado `reported`.
  - `remainingUsdEquivalent($includeReported=false)`.
  - `hasReportedPayments()`.
  - `isVoided()` — status `voided` o `reissued`.
  - Labels: `statusLabel()`, `lateFeeScopeLabel()`, `lateFeeTypeLabel()`, `lateFeeLabel()`.

### `App\Models\InvoiceItem` (BD tenant)
- **Fillable**: `invoice_id`, `apartment_id`, `expense_item_id` (nullable para reserva),
  `base_amount_usd`, `base_amount_ves`, `quantity` (int), `distributed` (bool),
  `is_reserve` (bool), `subtotal_usd`, `subtotal_ves`.
- **Relaciones**: `apartment()`, `expenseItem()`.

## Servicios

### `App\Services\BillingService`
- **`generateInvoice($period, $expenseItemIds, $apartmentIds, $lateFee, $towerId, $itemDetails)`**
  - Crea factura padre en `draft` dentro de `DB::transaction`.
  - Obtiene tasa activa de `CurrencyRate`.
  - `due_date` = último día del mes del periodo.
  - Llama a `createItems()` para distribuir gastos y calcular totales.
- **`regenerateInvoice($invoice, ...)`** — reconstruye ítems de un borrador (misma matemática).
- **`createItems()`** — distribución:
  - `aliquota`: reparte proporcional a `aliquot_percent` de cada apartamento.
  - `equal`: cada apartamento paga el monto completo × cantidad.
  - **Pool VES**: si el usuario tecleó `amount_ves`, se conserva (no se recalcula USD×tasa).
  - **Fondo de reserva por torre** (si `include_tower`): % de `Tower.reserve_percent` sobre
    el subtotal de gastos comunes del apto → `InvoiceItem` con `is_reserve=true`,
    `reserve_type='tower'`. Se acumula al total del apto para el cálculo del general.
  - **Fondo de reserva general** (si `include_general`): % de `Condominium.reserve_percent`
    sobre el **total del apto** (gastos + reserva de torre) → `InvoiceItem` con
    `is_reserve=true`, `reserve_type='general'`.
  - Permite `apartment_ids` por ítem (override del listado global).
- **`$reserveOpts`**: `['include_tower' => bool, 'include_general' => bool]` (default true).

## Controlador: `InvoiceController`

### Listado y detalle
- `index(Request)` — paginado (10/20/50). Admins ven todas; owners solo sus sub-facturas
  (`parent_id` not null + `apartment_id` in sus ownerships). Filtros: `period`, `status`,
  `tower_id`, `type` (parent|child|all), `created_from/to`.
- `show(Invoice)` — eager load items, tower, children, paymentReports. Firma + QR.
  Calcula `isParent` y `allChildrenPaid`.

### Creación / edición
- `create(Request)` — torres, apartamentos (según torre), items activos, tasa activa.
- `store(StoreInvoiceRequest, BillingService)` — delega a `generateInvoice`.
- `edit(Invoice)` — solo si `draft`. Prefill agrupado por `expense_item_id`.
- `update(UpdateInvoiceRequest, BillingService)` — solo `draft`; delega a `regenerateInvoice`.

### Aprobación (genera sub-facturas)
- `approve(Invoice, InvoiceVerificationService)`:
  - Solo si `draft` y tiene ítems.
  - En transacción: agrupa items por `apartment_id`, crea sub-factura por cada uno con
    número `INV-{period}-{correlative:06d}` (correlativo con `lockForUpdate`, reintento 5×).
    Snapshot del owner activo (`owner_name/email/document`).
  - Marca padre como `pending`.
  - Fuera de transacción: firma cada factura, envía `InvoiceCreatedMail` en cola a owners.
  - Audita `invoice_approved`.

### Marcar pagada
- `markPaid(Invoice, Request)`:
  - Solo admins. Bloquea si hay `PaymentReport` en `reported` pendientes de revisión.
  - Si es padre con hijas: requiere `cascade=true` (marca hijas y luego padre).
  - Si es hija: marca y verifica si todas las hermanas están pagadas → marca al padre.
  - Calcula `late_fee_accrued_usd/ves` con tasa activa.

### Anulación y reemisión
- `void(Invoice, Request)` — requiere `reason`. Status `voided`, `voided_at`, `void_reason`.
- `reissue(Invoice, Request, BillingService, InvoiceVerificationService)`:
  - Marca original `voided` con `reissued_by`.
  - Crea nuevo borrador clonado (mismos datos, status `draft`, totales 0).
  - Pendiente: ver código para el flujo completo de copia de items.

### PDF
- `pdf(Invoice, InvoiceVerificationService)` — Dompdf, fuente DejaVu Sans, letter portrait.
  Filtra items para residentes (solo su apartamento). Totales personalizados. QR + URL.

## Observadores

### `App\Observers\InvoiceObserver`
- `creating` / `updating` → `invalidateSignatureIfNeeded()`.
- **Campos críticos** que invalidan la firma: `status`, `total_usd`, `total_ves`,
  `exchange_rate_used`, `due_date`, `late_fee_*`, `owner_*`.
- `created` → audita `invoice_created`. `updated` → audita `invoice_updated`.

## Policy: `InvoicePolicy`
- `viewAny` → true (todos).
- `create` / `store` → `super_admin`, `condo_admin`, `tower_admin`.
- `markPaid` → admins.
- `void` / `reissue` → admins, solo sobre `pending`/`paid` no anuladas.
- `update` → solo `draft`; admins o el `created_by`.
- `view` → admins; owners solo sub-facturas de su apartamento (no padre).

## Rutas (`routes/web.php`, prefijo `invoices`, middleware `auth`)
- `invoices.index`, `invoices.create`, `invoices.store`, `invoices.show`,
  `invoices.edit`, `invoices.update`, `invoices.pdf`, `invoices.markPaid`.
- `payments.create` / `payments.store` (anidadas en invoice) → ver `cobro.md`.

## Reglas de negocio clave

1. **Factura padre** agrupa gastos; **sub-facturas hijas** son las que paga cada apto.
2. Solo se puede editar/anular/reemitir en estados permitidos (draft para editar).
3. La mora se calcula por periodos completos y se "congela" al pagar (`late_fee_accrued_*`).
4. La firma HMAC invalida automáticamente si cambian campos críticos (ver `verificacion_factura.md`).
5. El correlativo es secuencial y con `lockForUpdate` para evitar colisiones.
6. El fondo de reserva se agrega como ítem `is_reserve` al generar (ver `fondo_reserva.md`).
   Hay dos tipos (`reserve_type`): 'tower' y 'general', cada uno con su % configurable y
   toggleable por factura.

## Vistas
- `resources/views/invoices/` — index, create, edit, show, pdf.

## Casos de uso típicos
- Generar factura mensual del condominio (padre) → aprobar → se generan sub-facturas.
- Anular una factura aprobada con motivo y reemitir un borrador corregido.
- Marcar pagada una sub-factura (o cascada padre+hijas).
- Descargar PDF con QR de verificación.

## Notas / deudas
- JS duplicado entre `create.blade.php` y `edit.blade.php` (~300 líneas) — pendiente unificar.
- `parseDecimalValue` (coma decimal) corregido solo en create; replicar en edit.