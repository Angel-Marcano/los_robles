# Módulo: Auditoría

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Trazabilidad de acciones sensibles del sistema. Cada operación importante genera un
registro en `AuditLog` con usuario, acción, entidad, cambios e IP.

## Modelo

### `App\Models\AuditLog` (BD tenant)
- **Fillable**: `user_id`, `action`, `entity_type`, `entity_id`, `changes` (array), `ip`.
- **Casts**: `changes` array.
- **Relaciones**: `user()` → belongsTo `User`.
- Usa `UsesTenantConnection`.

## Servicio: `App\Services\AuditService`
- `log(string $action, string $entityType, ?int $entityId, array $changes = [])`:
  - Crea `AuditLog` con `user_id=Auth::id()`, `ip=request()->ip()`.

## Observadores que auditan
- `InvoiceObserver`:
  - `created` → `invoice_created` (toArray).
  - `updated` → `invoice_updated` (getChanges).
- `PaymentReportObserver`:
  - `created` → `payment_report_created_observer`.
  - `updated` → `payment_report_updated`.
- Controladores también llaman `AuditService::log` directamente:
  - `invoice_marked_paid`, `invoice_approved`, `invoice_voided`, `invoice_reissued`.
  - `payment_report_created`, `payment_report_approved`, `payment_report_rejected`.
  - `reserve_fund_income`, `reserve_fund_expense`.

## Controlador: `AuditLogController`
- `index(Request)`:
  - **Autorización**: solo `super_admin` o `condo_admin` (`authorizeView`).
  - Filtros: `entity_type`, `action`, `user_id`, `date_from`, `date_to`.
  - Paginado (10/20/50, default 20).
  - **Export CSV** si `?export=csv` (`exportCsvStream`):
    - Columnas: ID, Fecha, Usuario, Entidad, Acción, Entidad_ID, IP, Cambios (JSON).
    - Stream por chunks de 500.
  - Lista `distinctTypes`, `distinctActions`, `users` para los filtros.

## Rutas
- `audit-logs.index` (en `routes/web.php`).

## Vistas
- `resources/views/audit_logs/` — index.

## Reglas de negocio clave

1. **Auditoría pasiva**: los observers registran automáticamente cambios en Invoice y
   PaymentReport.
2. **Auditoría activa**: los controladores registran acciones explícitas (markPaid, approve,
   void, reissue, reserve fund movements).
3. **Solo admins** (`super_admin`, `condo_admin`) ven los logs.
4. **Export CSV** para análisis externo / cumplimiento.
5. `changes` guarda el array de cambios (toArray o getChanges según caso).

## Casos de uso típicos
- Investigar quién anuló una factura y por qué (`void_reason`).
- Ver historial de aprobaciones/rechazos de pagos.
- Exportar logs para auditoría externa.

## Notas
- No hay log de lectura (solo de escritura).
- `ip` se captura del request actual.