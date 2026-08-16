# Módulo: Moneda / Tasa de Cambio

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

El sistema opera en **doble moneda**: USD (referencia) y VES (Bolívares). Existe una sola
**tasa activa** a la vez (`CurrencyRate`). Todos los montos se guardan en ambas monedas y
la conversión se hace con la tasa vigente al momento de la operación.

## Modelo

### `App\Models\CurrencyRate` (BD tenant)
- **Fillable**: `base` (USD), `quote` (VES), `rate` (decimal:6), `valid_from` (datetime),
  `valid_to` (datetime), `active` (bool).
- **Casts**: `rate` decimal:6, `valid_from`/`valid_to` datetime, `active` bool.
- Usa `UsesTenantConnection`.

## Servicio: `App\Services\CurrencyService`
- `currentRate()` — la tasa activa más reciente (`active=true`, orderByDesc `valid_from`).
- `setRate(float $rate)`:
  - Desactiva todas las tasas activas (`update(['active'=>false])`).
  - Crea una nueva con `base='USD'`, `quote='VES'`, `valid_from=now()`, `active=true`.
  - **Solo hay una tasa activa a la vez.**

## Controlador: `CurrencyRateController`
- `index()` — historial de tasas (paginado 10, orderByDesc `valid_from`).
- `create()` — formulario.
- `store(Request, CurrencyService)` — valida `rate` (numérico > 0); llama a `setRate`.

## Rutas
- `rates.index`, `rates.create`, `rates.store` (resource en `routes/web.php`).

## Vistas
- `resources/views/rates/` — index, create.

## Reglas de negocio clave

1. **Una sola tasa activa** a la vez. Al crear una nueva, las anteriores se desactivan.
2. **USD es la moneda de referencia**: los saldos, totales y mora se calculan en USD.
3. **VES se conserva tal como se ingresa** (pool) en facturas para evitar redondeo al
   recalcular `USD × tasa`.
4. **Conversión en pagos**: `usd_equivalent = amount_usd + (amount_ves / exchange_rate_used)`.
5. La tasa se **captura** en cada operación (factura, reporte de pago) en
   `exchange_rate_used` para mantener el histórico inmutable.
6. `paid_exchange_rate` se guarda al marcar factura pagada (tasa del momento de pago).

## Casos de uso típicos
- Admin actualiza la tasa BCV semanalmente.
- Al generar factura, se usa la tasa activa para `exchange_rate_used` y `total_ves`.
- Al reportar pago en VES, se convierte a USD eq. con la tasa capturada.

## Notas
- No hay historial de tasas por fecha efectiva (solo `valid_from`); `valid_to` no se usa
  para selección.
- No hay validación de que `rate > 0` en `currentRate()` (se asume).