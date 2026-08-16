# Módulo: Clientes / Usuarios / Propietarios

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Gestión de usuarios del condominio, su relación con apartamentos (ownership), roles y
permisos (Spatie Permission), perfil y seguridad. Los usuarios son los "clientes" del
sistema: propietarios, residentes, administradores.

## Modelos

### `App\Models\User` (BD tenant)
- **Authenticatable** + `HasApiTokens` (Sanctum) + `HasRoles` (Spatie) + `UsesTenantConnection`.
- **Fillable**: `name`, `first_name`, `last_name`, `document_type`, `document_number`,
  `email`, `password`, `active`.
- **Hidden**: `password`, `remember_token`, `two_factor_secret`, `two_factor_code`.
- **Casts**: `email_verified_at`, `active` (bool), `two_factor_code_expires_at`,
  `two_factor_confirmed_at` (datetime).
- **2FA**: `twoFactorEnabled()` — true si `two_factor_method` y `two_factor_confirmed_at`
  no son null. `generateTwoFactorCode()` — código 6 dígitos, hasheado, expira 10 min.
- **Roles** (Spatie, tablas en BD tenant): `super_admin`, `condo_admin`, `tower_admin`,
  `owner`, `tenant`.
- Scope `active` → `where('active', true)`.

### `App\Models\Ownership` (BD tenant)
- Vincula un usuario con un apartamento.
- **Fillable**: `apartment_id`, `user_id`, `role` (`owner`|`co_owner`|`tenant`), `active` (bool).
- **Relaciones**: `user()` → belongsTo `User`; `apartment()` → belongsTo `Apartment`.
- Un usuario puede tener varios ownerships (varios apartamentos).

## Roles y permisos (Spatie)

Config en `config/permission.php`:
- `permission` → `App\Models\Tenant\Permission`
- `role` → `App\Models\Tenant\Role`
- Tablas: `roles`, `permissions`, `model_has_permissions`, `model_has_roles`,
  `role_has_permissions`.

### Roles definidos
| Rol | Alcance |
|-----|---------|
| `super_admin` | Acceso total (incluye fondo de reserva, gastos, auditoría). |
| `condo_admin` | Administra el condominio: facturas, pagos de facturas sin torre. |
| `tower_admin` | Administra su torre: facturas con `tower_id`, aprueba pagos de su torre. |
| `owner` | Propietario: ve sus sub-facturas, reporta pagos. |
| `tenant` | Residente/inquilino: similar a owner para pagos. |

## Controladores

### `UserController`
- `index` (paginado 10), `create`, `store`, `edit`, `update`, `destroy`, `toggle`.
- `store` valida: `name`, `first_name`, `last_name`, `document_type` (cedula|pasaporte),
  `document_number`, `email` (unique), `password` (min 6). Hashea password.
- `update` permite parcial (`sometimes`); hashea password si viene; `active` bool.
- `toggle` activa/desactiva usuario.

### `OwnershipController`
- `index(Apartment)` — lista owners del apartamento (con `user`).
- `store(Request, Apartment)` — valida `user_id` (exists tenant.users) y `role`
  (owner|co_owner|tenant). `firstOrCreate` por (apartment_id, user_id).
- `destroy(Apartment, Ownership)` — elimina (verifica que pertenezca al apartamento).
- `toggle(Apartment, Ownership)` — activa/desactiva ownership.

### `ProfileController`
- `edit` / `update` — datos del perfil (name, first_name, last_name, document, email).
- `updatePassword` — valida `current_password` y nueva (min 6, confirmed).

## Policies

### `OwnershipPolicy`
- `viewAny`, `create`, `delete`, `toggle` → solo `super_admin`, `condo_admin`, `tower_admin`.

### (Sin policy propia para User — acceso libre a admin; ver `UserController`.)

## Reglas de negocio clave

1. **Un usuario puede ser propietario/co-propietario/inquilino de varios apartamentos.**
2. **Ownership activo** determina qué facturas ve un owner y sobre qué apartamentos
   puede reportar pagos / consultar saldos.
3. **Snapshot de owner** al aprobar factura: se copia `name`, `email`, `document_type`,
   `document_number` a la sub-factura (`owner_name`, `owner_email`, `owner_document`).
4. **Roles por BD tenant**: cada condominio gestiona sus propios roles/permisos.
5. **2FA** opcional por usuario (email o TOTP) — ver `auth_seguridad.md`.

## Rutas

- `users.*` (resource) — gestión de usuarios.
- `ownerships.index` (anidada en apartment), `ownerships.store`, `ownerships.destroy`.
- `profile.edit`, `profile.update`, `profile.password`.

## Vistas

- `resources/views/users/` — index, create, edit.
- `resources/views/ownerships/` — index (por apartamento).
- `resources/views/profile/` — edit.

## Casos de uso típicos

- Crear usuario propietario al crear un apartamento (flujo rápido en `ApartmentController`).
- Asignar varios propietarios/inquilinos a un apartamento.
- Activar/desactivar un ownership sin borrar el usuario.
- Editar perfil y cambiar contraseña.
- Activar 2FA desde el perfil.

## Notas

- `document_type` limitado a `cedula` o `pasaporte`.
- No hay validación de unicidad de `document_number`.
- El alta de usuario desde `ApartmentController` genera contraseña aleatoria si no se indica.