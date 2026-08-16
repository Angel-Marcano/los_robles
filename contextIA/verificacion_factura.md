# Módulo: Verificación de Facturas (Firma + QR)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Cada factura aprobada/pagada recibe una **firma HMAC** que garantiza su integridad. Se
genera un **token** y un **QR** que apuntan a una página pública de verificación. Si
cambian campos críticos luego de firmada, la firma se invalida automáticamente.

## Servicio: `App\Services\InvoiceVerificationService`

### Firma
- `ensureSignature(Invoice)`:
  - Solo firma si status es `pending` o `paid`.
  - Si ya tiene `invoice_signature` y `signed_at`, no hace nada.
  - Calcula y guarda la firma + `signed_at` con `saveQuietly` (no dispara observer).
- `computeInvoiceSignature(Invoice)`:
  - **Payload** (unido por `|`): `id`, `tenantId`, `status`, `total_usd`, `total_ves`,
    `exchange_rate_used`, `due_date` (Y-m-d), `owner_name`, `owner_email`, `owner_document`.
  - **Algoritmo**: `hash_hmac('sha256', $payload, $hmacKey())`.
  - `hmacKey()` — deriva de `APP_KEY` (decodifica base64: si aplica).

### Token
- `generateToken(Invoice)`:
  - Asegura firma con `ensureSignature`.
  - Payload JSON: `{i: invoice_id, t: tenant_id, s: signature}`.
  - Codifica base64url y agrega MAC: `v1.{payload}.{mac}`.
- `verificationUrl(Invoice)` → `route('verify.invoice.short', ['token' => ...])` (`/v/{token}`).
- `qrSvgForInvoice(Invoice, $size=160)` — SVG del QR (BaconQrCode) apuntando a la URL.

### Verificación
- `verifyToken(string $token): array`:
  - Formato `v1.{payload}.{mac}`; valida MAC con `hash_equals`.
  - Decodifica payload; valida `tenant_id` coincida con el actual.
  - Busca factura **con trashed** (`withTrashed`).
  - Estados de retorno:
    - `no-existe` — token inválido, MAC incorrecta, tenant distinto, o factura no existe.
    - `anulada` — factura en trash, `isVoided()`, sin firma, o firma no coincide.
    - `valida` — todo correcto.

## Observador: `InvoiceObserver`
- `invalidateSignatureIfNeeded()`:
  - Si la factura ya está firmada y cambia un **campo crítico**, la firma se invalida.
  - **Campos críticos**: `status`, `total_usd`, `total_ves`, `exchange_rate_used`,
    `due_date`, `late_fee_type`, `late_fee_scope`, `late_fee_value`, `owner_name`,
    `owner_email`, `owner_document`.
  - (La invalidación ocurre en `creating`/`updating` — ver implementación para detalle.)

## Controlador: `VerifyInvoiceController`
- `show(string $token, InvoiceVerificationService)`:
  - Llama `verifyToken()` y retorna vista `verify.invoice` con `status` e `invoice`.

## Rutas (públicas, sin auth)
- `verify.invoice` → `GET /verify/invoice/{token}`
- `verify.invoice.short` → `GET /v/{token}` (URL corta para QR)

## Vistas
- `resources/views/verify/invoice.blade.php` — página pública de verificación.

## Reglas de negocio clave

1. **Firma solo para `pending`/`paid`** (no draft, no voided).
2. **Integridad**: cambiar campos críticos invalida la firma → verificación falla.
3. **Tenant-bound**: el token incluye `tenant_id`; no se puede verificar una factura de
   otro condominio desde otro tenant.
4. **URL corta** (`/v/{token}`) para QR compacto.
5. **Incluso facturas eliminadas** (soft delete) se pueden verificar (aparecen como anuladas).

## Casos de uso típicos
- Propietario descarga PDF con QR → escanea → ve "Factura válida".
- Admin anula una factura → al verificar, aparece "anulada".
- Cambio de monto por error → firma invalidada → verificación falla.

## Notas
- `APP_KEY` es la raíz de la firma; si rota, todas las verificaciones previas fallan.
- El QR se genera como SVG inline en el PDF (no requiere assets externos).