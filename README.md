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

## Seeds iniciales sugeridos (no incluidos aún)

Se pueden crear comandos para poblar:
- Torres y apartamentos base.
- Ítems de gasto frecuentes (mantenimiento, limpieza, seguridad, agua, electricidad).
- Tasa de cambio inicial.

## Testing rápido

1. Crear condominio: `php artisan tenants:create "Condo Demo" demo_condo db_demo_condo`.
2. Edita tu archivo hosts para apuntar `demo_condo.local.test`.
3. Accede vía navegador al subdominio y genera invoice.
4. Verifica tabla `invoices` en DB `db_demo_condo`.

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
