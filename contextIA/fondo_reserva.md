# Módulo: Fondo de Reserva

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Existen **dos tipos** de fondo de reserva:
1. **Fondo general del condominio** — un solo fondo para todo el condominio (`tower_id=null`).
2. **Fondo por torre** — cada torre tiene su propio fondo aislado.

Ambos se alimentan automáticamente cuando una factura se marca como pagada, y admiten
movimientos manuales. Los **% son configurables** en una pantalla de Configuración, y se
pueden **activar/desactivar por factura** mediante checkboxes en el formulario de factura.

## Modelos

### `App\Models\ReserveFund` (BD tenant)
- **Fillable**: `tower_id` (nullable), `condominium_id` (nullable), `name`, `balance_usd`, `balance_ves`.
- **Casts**: `balance_usd`, `balance_ves` (decimal:2).
- **Relaciones**: `tower()` → belongsTo `Tower`; `condominium()` → belongsTo `Condominium`;
  `movements()` → hasMany `ReserveFundMovement`.
- **`forTower(Tower)`** — obtiene o crea el fondo de una torre (uno por torre).
- **`forCondominium(Condominium)`** — obtiene o crea el fondo general (`tower_id=null`).
- **`isGeneral()`** — true si `tower_id === null`.

### `App\Models\ReserveFundMovement` (BD tenant)
- **Fillable**: `reserve_fund_id`, `direction` (`income`|`expense`), `source`
  (`invoice`|`manual`|`adjustment`), `reserve_type` (`tower`|`general`, nullable),
  `invoice_id`, `apartment_id`, `amount_usd`, `amount_ves`, `exchange_rate`, `notes`, `user_id`.
- **Casts**: montos decimal:2, `exchange_rate` decimal:6.
- **Relaciones**: `reserveFund()`, `invoice()`, `apartment()`, `user()`.
- **Labels**: `directionLabel()` (Ingreso/Egreso), `sourceLabel()` (Factura/Manual/Ajuste).

## Servicio: `App\Services\ReserveFundService`

### `registerMovement(ReserveFund, $direction, array $data)`
- En transacción: suma (income) o resta (expense) al saldo USD/VES del fondo.
- Crea el `ReserveFundMovement` con `user_id=auth()->id()`.

### `creditFromPaidInvoice(Invoice)`
- Se llama al marcar una factura como `paid` (desde `PaymentReportController::approve`).
- **Separa ítems `is_reserve` por `reserve_type`** ('tower' | 'general').
- Para cada tipo:
  - **Anti-doble crédito**: verifica que no exista movimiento `income` con `source='invoice'`
    y el mismo `reserve_type` para esa factura.
  - Calcula `fraction = reserveUsd / dueUsdEquivalent`.
  - Reparte el crédito según lo **realmente pagado** (USD y VES aprobados) × fracción.
  - Fallback: montos nominales si no hay datos de pago.
  - **Fondo destino**: 'tower' → `ReserveFund::forTower($tower)`; 'general' →
    `ReserveFund::forCondominium($condo)`.
  - Crea movimiento `income` con `source='invoice'`, `reserve_type`, notas descriptivas.

## Controlador: `ReserveFundController`
- `index()`:
  - Asegura que cada torre tenga su fondo (`ReserveFund::forTower`).
  - Asegura el fondo general del condominio (`ReserveFund::forCondominium`).
  - Lista fondos con torre; el general aparece primero. Totales USD/VES.
  - Pasa `condominium` a la vista para mostrar el % general.
- `show(ReserveFund)`:
  - Lista movimientos con `invoice`, `apartment`, `user`.
  - Calcula **saldo acumulado** (running balance) por movimiento (USD y VES).
  - Muestra del más reciente al más antiguo.
- `createMovement(ReserveFund)` — formulario.
- `storeMovement(Request, ReserveFund, ReserveFundService)`:
  - Valida `direction` (income|expense), `amount_usd`/`amount_ves` (min 0), `exchange_rate`,
    `notes`.
  - Requiere al menos un monto > 0.
  - Si `expense`: valida fondos suficientes (tolerancia 0.005).
  - Llama a `registerMovement` con `source='manual'`.
  - Audita `reserve_fund_{direction}`.

## Policy: `ReserveFundPolicy`
- `viewAny`, `view` → `super_admin`, `condo_admin`, `tower_admin` (ven).
- `manage` → `super_admin`, `condo_admin` (editan % y movimientos manuales).

## Configuración de % — `ReserveConfigController`
- `edit()` — muestra formulario con: % general del condominio + tabla de torres con su %.
- `update(Request)` — guarda % general (`Condominium.reserve_percent`) y % por torre
  (`Tower.reserve_percent`). Audita cambios.
- Rutas: `reserve-funds.config.edit` (GET), `reserve-funds.config.update` (PATCH).
- Vista: `resources/views/reserve-funds/config.blade.php`.
- Acceso: `super_admin` y `condo_admin` (nav link "Config. Reserva").

## Rutas
- `reserve-funds.index`, `reserve-funds.show`, `reserve-funds.movements.create`,
  `reserve-funds.movements.store`.
- `reserve-funds.config.edit`, `reserve-funds.config.update`.

## Vistas
- `resources/views/reserve-funds/` — index, show, movement.

## Reglas de negocio clave

1. **Dos tipos de fondo**: general del condominio (`tower_id=null`) + uno por torre.
2. **% configurable**: `Condominium.reserve_percent` (general) y `Tower.reserve_percent` (torre),
   editables en la pantalla "Config. Reserva".
3. **Toggles por factura**: checkboxes `include_tower_reserve` e `include_general_reserve`
   en el formulario de factura. Marcados por defecto si el % > 0. Permiten excluir uno o
   ambos en una factura específica.
4. **Base del % general**: se aplica sobre el **total de la factura del apartamento**
   (gastos comunes + reserva de torre si la hubo).
5. **Base del % de torre**: se aplica sobre el **subtotal de gastos comunes** del apto.
6. **Aporte automático** al pagar factura: proporcional a lo cobrado, no a lo facturado.
7. **Anti-doble crédito**: verifica `(invoice_id, source, direction, reserve_type)` único.
8. **Movimientos manuales** solo por `super_admin` y `condo_admin`.
9. `InvoiceItem.reserve_type` ('tower'|'general') distingue el tipo de reserva en la factura.

## Casos de uso típicos
- Ver saldo acumulado del fondo de una torre con su historial.
- Registrar un egreso manual (uso del fondo para reparación).
- Al aprobar el último pago de una factura, el fondo recibe el aporte automáticamente.

## Notas
- El aporte se calcula sobre lo **pagado**, no sobre lo facturado (más justo si hay abonos parciales).
- `exchange_rate` del movimiento viene de `invoice.paid_exchange_rate ?? exchange_rate_used`.