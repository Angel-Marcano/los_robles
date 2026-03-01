# Los Robles – Plataforma Multi-Tenancy para Condominios

Este proyecto Laravel 10 implementa un esquema multi-tenant por subdominio y base de datos dedicada para cada condominio (tenant). El objetivo: aislar datos financieros y operativos de cada cliente, permitiendo escalar el sistema como SaaS.

## Arquitectura

Conceptos clave:

1. Landlord DB (principal): contiene la tabla `condominiums` y usuarios (`users`).
2. Tenant DB (una por condominio activo con `db_name`): almacena tablas de dominio (towers, apartments, expense_items, invoices, etc.).
3. Resolución por subdominio: Middleware `IdentifyCondominium` detecta el subdominio y configura dinámicamente la conexión `tenant` (`config(['database.connections.tenant' => ...])`).
4. Modelos de dominio: usan el trait `UsesTenantConnection` para apuntar a la conexión tenant si está definida.
5. Aislamiento de datos: No se guarda `condominium_id` en tablas tenant. El contexto se infiere por la conexión activa.

## Flujo de Request
1. Llega petición a `app` (ej: https://mi-condo.ejemplo.com).
2. Middleware `IdentifyCondominium` resuelve el condominio por `subdomain` y configura conexión tenant.
3. Los modelos con `UsesTenantConnection` trabajan automáticamente sobre la DB del condominio.
4. Usuarios se consultan en DB landlord; relaciones cruzadas (ej. `ownerships.user_id`) mantienen integridad lógica (sin FK física a usuarios en tenant).

## Alta de un nuevo condominio (Tenant)

Artisan automatiza el proceso:

```bash
php artisan tenants:create {name} {subdomain} {db_name}
```

Pasos ejecutados:
- Inserta registro en `condominiums` con `subdomain`, `db_name` y `active`.
- Crea la base de datos física (requiere privilegios MySQL adecuados).
- Ejecuta migraciones de `database/migrations/tenant` en la nueva BD.
- (Opcional) Seeding inicial si se agrega lógica futura.

Para aplicar migraciones tenant a todos los existentes:

```bash
php artisan tenants:migrate
```

## Migraciones

Las migraciones landlord siguen en `database/migrations` (Laravel default). Migraciones tenant consolidadas en `database/migrations/tenant/*`. Se añadió la migración principal `create_domain_tables` que levanta todas las entidades necesarias en la BD aislada.

Si agregas nuevas columnas a tablas tenant ya creadas en producción, crea una migración incremental (no edites la existente para preservar histórico). Ejemplo: `php artisan make:migration add_field_x_to_invoices --path=database/migrations/tenant`.

## Modelos Principales (Tenant)

| Modelo        | Propósito                           |
|---------------|-------------------------------------|
| Tower         | Torres del condominio               |
| Apartment     | Unidades habitacionales             |
| ExpenseItem   | Ítems de gasto (fijo o alícuota)    |
| Invoice       | Facturas mensuales generadas        |
| InvoiceItem   | Detalle distribuido por apartamento |
| Account / Movements | Cuentas y movimientos contables |
| Ownership     | Relación apartamento → usuario      |
| PaymentReport | Reportes de pago sobre facturas     |
| CurrencyRate  | Tasa de cambio histórica            |
| AuditLog      | Registros de acciones (auditoría)   |

## Facturación

Servicio `BillingService`:
- Método `generateInvoice(period, expenseItemIds, apartmentIds, lateFee, towerId)` crea factura y distribuye montos.
- Tipos de gasto:
	- fixed: se replica el monto ingresado por cada apartamento.
	- aliquot: se prorratea el monto total entre apartamentos seleccionados.
- Se calculan totales USD y VES usando la tasa activa (`currency_rates`).
- Envío de correo (queue) a usuarios propietarios relacionados vía `ownerships`.

Campos adicionales en `invoice_items`:
- `base_amount_usd`: monto base (pool o fijo original).
- `distributed`: bandera booleana (true si es prorrateado).

## Roles y Accesos

- super_admin / condo_admin: acceso completo dentro del tenant.
- tower_admin: restringido a su torre y apartamentos asociados.
- Otros roles (propietario, inquilino) pueden tener vistas limitadas (pendiente ampliar políticas específicas).

## Subdominios y DNS

Para entorno local puedes mapear hosts en tu archivo `hosts`:

```
127.0.0.1    condo1.local.test
127.0.0.1    condo2.local.test
```

En producción: crear registros A/Wildcard (`*.midominio.com`) apuntando al load balancer o servidor.

## Comandos Artisan para Tenants

### 1. Crear un nuevo condominio (tenant)

```bash
php artisan tenants:create {name} {subdomain} [--db=nombre_bd] [--seed]
```

Ejemplo:

```bash
php artisan tenants:create "Los Robles" losrobles --db=db_losrobles --seed
```

Esto:
- Inserta registro en `condominiums` con `subdomain`, `db_name` y `active`.
- Crea la base de datos física (requiere privilegios MySQL).
- Ejecuta migraciones tenant.
- Con `--seed` ejecuta seeders iniciales.

### 2. Poblar torres, apartamentos y usuarios

```bash
php artisan tenants:seed-structure {subdomain} [--towers=A,B,C] [--domain=user.com] [--password=12345678] [--dry-run]
```

Ejemplo completo:

```bash
php artisan tenants:seed-structure losrobles --towers=A,B,C --domain=losrobles.com --password=12345678
```

| Opción | Default | Descripción |
|--------|---------|-------------|
| `--towers=A,B,C` | `A,B,C` | Torres a crear (separadas por coma) |
| `--domain=user.com` | `user.com` | Dominio para emails auto-generados |
| `--password=12345678` | `12345678` | Contraseña de usuarios creados |
| `--dry-run` | – | Solo muestra qué haría, sin escribir en BD |

**¿Qué crea por cada torre?** (ej. Torre A):

| Recurso | Detalle |
|---------|---------|
| Torre | `Torre A` |
| Apartamentos | `A-01` a `A-04` (PB), `A-11` a `A-44` (pisos 1-4), `A-51` a `A-54` (piso 5) |
| Alícuotas | PB: 4.406% (01,02,04), 3.385% (03); Pisos 1-4: 4.084%; Piso 5: 4.514% |
| Usuarios | `user_A_01@losrobles.com`, `user_A_02@losrobles.com`, etc. |
| Ownerships | Cada usuario asignado como `owner` de su apartamento |

Para ver el resultado sin modificar la BD:

```bash
php artisan tenants:seed-structure losrobles --towers=A,B,C --dry-run
```

### 3. Crear roles en todos los tenants

```bash
php artisan tenants:seed-roles
```

Crea los roles `super_admin`, `condo_admin`, `tower_admin` en cada BD tenant activa (idempotente).

### 4. Crear admin en todos los tenants

```bash
php artisan tenants:seed-admins [--password=1234]
```

Crea usuario `admin@admin.com` con rol `super_admin` en cada BD tenant que no lo tenga.

### 5. Migrar todos los tenants

```bash
php artisan tenants:migrate
```

Ejecuta las migraciones de `database/migrations/tenant` en todas las BDs tenant activas.

## Setup completo desde cero (ejemplo)

```bash
# 1. Crear el tenant
php artisan tenants:create "Los Robles" losrobles --db=db_losrobles

# 2. Crear roles base
php artisan tenants:seed-roles

# 3. Crear usuario administrador (admin@admin.com / 1234)
php artisan tenants:seed-admins --password=1234

# 4. Poblar torres A, B, C con apartamentos y usuarios
php artisan tenants:seed-structure losrobles --towers=A,B,C --domain=losrobles.com --password=12345678

# 5. Configurar hosts (Windows: C:\Windows\System32\drivers\etc\hosts)
#    127.0.0.1    losrobles.localhost

# 6. Acceder en navegador: http://losrobles.localhost
#    Login admin: admin@admin.com / 1234
#    Login usuario: user_A_01@losrobles.com / 12345678
```

## Testing rápido

1. Crear condominio: `php artisan tenants:create "Condo Demo" demo --db=db_demo`.
2. Poblar estructura: `php artisan tenants:seed-structure demo --towers=A,B`.
3. Crear admin: `php artisan tenants:seed-admins`.
4. Edita tu archivo hosts: `127.0.0.1 demo.localhost`.
5. Accede vía navegador a `http://demo.localhost` y genera facturas.
6. Verifica tabla `invoices` en DB `db_demo`.

## Consideraciones y Próximos Pasos

Pendiente / sugerido:
- Migración incremental para mover datos legacy desde landlord a tenant (si había facturas previas).
- Sincronización/replicación opcional de usuarios específicos al tenant (si se requiere aislamiento total en el futuro).
- Cacheo de configuración tenant (optimizar arranque del middleware).
- Implementar políticas específicas por modelo (actualmente algunas autorizaciones reutilizan create).
- Endpoints API documentados con OpenAPI/Swagger.

## Mantenimiento

Cuando se cambia la estructura de `BillingService` o migraciones tenant:
1. Crear migración incremental.
2. Ejecutar `php artisan tenants:migrate`.
3. Validar en uno o dos tenants antes de masivo.

## Seguridad

Datos se aíslan a nivel de conexión. Evita usar modelos landlord dentro del tenant salvo que sea inevitable (usuarios). Revisa queries manuales para no cruzar accidentalmente la conexión `mysql` en lectura de datos sensibles.

## Performance

- El cambio de conexión ocurre una vez por request vía middleware.
- Considera añadir índices en tablas de alto volumen (invoice_items: `invoice_id`, `apartment_id`).
- Para exportaciones grandes se usa `chunk()` y streaming CSV para memoria constante.

## Troubleshooting

| Problema | Causa común | Solución |
|----------|-------------|----------|
| No resuelve tenant | Subdominio no registrado | Verificar DNS / hosts y campo `subdomain` en `condominiums` |
| Error columna `condominium_id` | Código legacy aún activo | Limpiar controladores y vistas antiguos (ya refactorado) |
| Foreign key falla en tenant | Intento de FK a `users` | No crear FK a usuarios (están en landlord) |
| Columnas faltan en `invoice_items` | Tenants antiguos | Crear migración incremental para añadir `base_amount_usd` y `distributed` |

## Licencia

Software interno; derivado de Laravel (MIT). El framework mantiene su licencia original.

---
Documentación generada para la fase de multi-tenancy. Mantener este README actualizado con nuevas decisiones arquitectónicas.
