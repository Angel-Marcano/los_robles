# Módulo: Cuentas Internas y Movimientos

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Cuentas internas del condominio (bancos, caja, etc.) con saldos en USD y VES. Permite
depósitos, retiros, transferencias entre cuentas y **cambio de divisas** (USD↔VES).

## Modelos

### `App\Models\Account` (BD tenant)
- **Fillable**: `owner_type`, `owner_id`, `name`, `balance_usd`, `balance_ves`, `condominium_id`.
- **Casts**: `balance_usd`, `balance_ves` (decimal:2).
- **Relaciones**: `movements()` → hasMany `AccountMovement`; `condominium()` → belongsTo.
- `owner_type='system'`, `owner_id=0` para cuentas del sistema.

### `App\Models\AccountMovement` (BD tenant)
- **Fillable**: `account_id`, `type` (`deposit`|`withdraw`|`transfer_in`|`transfer_out`),
  `amount_usd`, `amount_ves`, `reference`, `user_id`, `meta` (array).
- **Casts**: `amount_usd`, `amount_ves` (decimal:2), `meta` (array).

### `App\Models\ExchangeTransaction` (BD tenant)
- Registra un cambio de divisas entre dos cuentas.
- **Fillable**: `account_origin_id`, `account_target_id`, `rate`, `amount_origin_usd`,
  `amount_origin_ves`, `amount_target_usd`, `amount_target_ves`.
- **Casts**: `rate` decimal:6, montos decimal:2.

## Servicio: `App\Services\AccountService`
- `moveFunds(Account $from, Account $to, float $usd=0, float $ves=0, ?string $reference)`:
  - En transacción: decrement origen, increment destino (USD y/o VES).
  - Crea dos `AccountMovement`: `transfer_out` (origen) y `transfer_in` (destino),
    con `meta` indicando la contraparte.

## Controladores

### `AccountController`
- `index()` — paginado 30, orderBy `name`.
- `create()` / `store(Request)` — valida `name` (max 120), `balance_usd`, `balance_ves`
  (nullable numérico, default 0). Crea con `owner_type='system'`, `owner_id=0`.
- `edit(Account)` / `update(Request, Account)` — solo actualiza `name`.

### `AccountMovementController`
- `create(Account)` — formulario de depósito/retiro.
- `store(Request, Account)`:
  - Valida `type` (deposit|withdraw), `amount_usd`/`amount_ves` (min 0), `reference`.
  - Deposit: incrementa saldo. Withdraw: valida fondos suficientes, decrementa.
  - Crea `AccountMovement`.
- `transferForm()` — lista cuentas.
- `transferStore(Request, AccountService)`:
  - Valida `from_id` ≠ `to_id`, montos > 0, fondos suficientes en origen.
  - Llama a `AccountService::moveFunds()`.

### `ExchangeTransactionController`
- `create()` — lista cuentas + tasa activa.
- `store(Request)`:
  - Valida `origin_id`, `target_id`, `direction` (`usd_to_ves`|`ves_to_usd`), `amount`,
    `rate`, `reference`.
  - Valida fondos suficientes en la moneda origen.
  - En transacción: decrementa origen, increment destino, crea `ExchangeTransaction`.
  - `usd_to_ves`: `ves = usd × rate`. `ves_to_usd`: `usd = ves / rate`.

## Rutas
- `accounts.index`, `accounts.create`, `accounts.store`, `accounts.edit`, `accounts.update`.
- Movimientos y transferencias (rutas en `routes/web.php`).
- `exchange.create`, `exchange.store`.

## Vistas
- `resources/views/accounts/` — index, create, edit, `movements/create`, `movements/transfer`.
- `resources/views/exchange/` — create.

## Reglas de negocio clave

1. **Saldos en doble moneda** (USD y VES) por cuenta.
2. **Transferencias** crean dos movimientos espejo (in/out) con `meta` cruzada.
3. **Cambio de divisas** usa una `rate` ingresada (no necesariamente la tasa activa del
   sistema) y registra `ExchangeTransaction`.
4. **Validación de fondos** en retiros, transferencias y cambios.
5. No hay policy explícita — acceso por rol implícito (admin).

## Casos de uso típicos
- Crear cuenta bancaria del condominio con saldo inicial.
- Depositar retiro de efectivo de caja.
- Transferir USD de cuenta banco a cuenta caja.
- Cambiar VES a USD entre dos cuentas con tasa del día.

## Notas
- `Account` no tiene policy; el acceso lo controla el rol del usuario (admin).
- `ExchangeTransaction` no tiene relaciones definidas a `Account`.