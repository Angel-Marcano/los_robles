# Módulo: Condominios, Torres y Apartamentos (Multi-tenancy)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

El sistema administra múltiples condominios. Cada condominio es un **tenant** con su
propia base de datos (conexión `tenant`). La resolución del tenant se hace por subdominio
/host y se guarda en el service container como `currentCondominium`.

## Modelos

### `App\Models\Condominium` (BD master, tabla `condominiums`)
- **Fillable**: `name`, `active`, `subdomain`, `db_name`.
- **Relaciones**: `towers()` → hasMany `Tower`.
- **Métodos**: `hasDedicatedDatabase()` — indica si tiene BD dedicada.
- **No** usa el trait `UsesTenantConnection` (vive en la BD master).

### `App\Models\Tower` (BD tenant)
- **Fillable**: `name`, `active`, `reserve_percent` (decimal:2, % de reserva).
- **Relaciones**: `apartments()` → hasMany `Apartment`; `reserveFund()` → hasOne `ReserveFund`.
- Usa `UsesTenantConnection`.

### `App\Models\Apartment` (BD tenant)
- **Fillable**: `tower_id`, `code`, `active` (bool), `aliquot_percent` (decimal:8).
- **Relaciones**: `tower()` → belongsTo `Tower`; `ownerships()` → hasMany `Ownership`.
- `aliquot_percent` define la prorrata para distribución de gastos comunes.

## Trait multi-tenant

`App\Models\Traits\UsesTenantConnection`:
- Si `app()->bound('currentCondominium')` y existe la conexión `tenant`, retorna `'tenant'`.
- Si no, cae a la conexión por defecto del modelo.
- Loguea (info) la resolución la primera vez por modelo (ruido en logs — pendiente limpiar).

## Controladores

### `CondominiumController` (gestión de condominios — BD master)
- `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Validación: `name` requerido (max 120); `active` sometimes boolean.
- Rutas resource estándar (`condominiums.*`).

### `TowerController` (BD tenant)
- `index`, `create`, `store`, `edit`, `update`, `destroy`.
- Validación: `name` (max 120), `active` bool, `reserve_percent` numérico 0–100 (default 0).
- Todas las torres pertenecen al condominio implícito (DB actual).

### `ApartmentController` (BD tenant)
- `index(Tower $tower)` — lista paginada (10) ordenada por `code`.
- `create(Tower $tower)` / `store(Request, Tower)`:
  - Valida `code` único por torre (`unique:tenant.apartments,code,NULL,id,tower_id,{tower}`).
  - `aliquot_percent` requerido (> 0.0001).
  - Puede crear usuario propietario + `Ownership` si llega `owner_email` (y opcional `owner_password`).
  - Si no se indica contraseña, se genera una aleatoria y se muestra aviso.
- `edit` / `update` / `destroy` estándar.

## Reglas de negocio clave

1. **Un condominio = una BD** (conexión `tenant`). Las migraciones tenant viven en
   `database/migrations/tenant`.
2. **Deriva de esquema**: hay migraciones en `database/migrations` (master) y en
   `database/migrations/tenant` que tocan tablas parecidas — pendiente documentar cuál manda.
3. **Torre** agrupa apartamentos y tiene su propio **fondo de reserva** aislado.
4. **Apartamento** tiene `aliquot_percent` que se usa para repartir gastos comunes
   (distribución `aliquota`).

## Rutas

Definidas en `routes/web.php` (resource estándar). Ejemplos:
- `condominiums.index`, `condominiums.store`, etc.
- `towers.index`, `towers.store`, etc.
- `apartments.index` (anidada en torre).

## Vistas

- `resources/views/condominiums/` — index, create, show, edit.
- `resources/views/towers/` — index, create, edit.
- `resources/views/apartments/` — index, create (y edit implícito).

## Casos de uso típicos

- Crear un condominio nuevo (define subdominio + BD).
- Crear torres dentro del condominio y fijar `reserve_percent`.
- Crear apartamentos por torre con su alícuota y (opcional) propietario inicial.

## Notas / deudas técnicas

- Los `Log::info` del trait `UsesTenantConnection` ensucian logs (mejoras_futuras.txt).
- Validación de `aliquot_percent` no suma 100% por torre (no se valida consistencia).