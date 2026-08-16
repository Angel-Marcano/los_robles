# Módulo: Gastos (ExpenseItem)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Catálogo de **conceptos facturables** (gastos comunes): limpieza, vigilancia, electricidad
de áreas comunes, etc. Un `ExpenseItem` solo define nombre y estado; el **monto** se
especifica al generar la factura. Se usan para construir los `InvoiceItem`.

## Modelo

### `App\Models\ExpenseItem` (BD tenant)
- **Fillable**: `name`, `type` (`fixed`|`aliquot`), `active` (bool).
- **Casts**: `active` bool.
- Usa `UsesTenantConnection`.
- **No** define relaciones (el monto vive en `InvoiceItem`).
- `type` indica la distribución sugerida, pero la distribución real se elige al facturar
  (`aliquota` o `equal` en `BillingService`).

## Controlador: `ExpenseItemController`
- `index(Request)` — paginado 30, orderBy `name`. Requiere `viewAny`.
- `create()` — formulario.
- `store(Request)`:
  - Valida `name` (max 120), `type` (nullable, fixed|aliquot, default `fixed`),
    `active` (required bool).
  - Crea el item.
- `storeInline(Request): JsonResponse` — creación rápida (para selects en form de factura).
  - Valida `name`, `type` (nullable), `active` (nullable, default true).
  - Retorna `{id, name, type, active}` (201).
- `edit(ExpenseItem)` / `update` / `destroy` estándar.

## Policy: `ExpenseItemPolicy`
- `viewAny`, `create`, `update`, `delete` → **solo `super_admin`**.

## Rutas
- `expense-items.index`, `expense-items.create`, `expense-items.store`,
  `expense-items.storeInline`, `expense-items.edit`, `expense-items.update`,
  `expense-items.destroy` (en `routes/web.php`).

## Vistas
- `resources/views/expense-items/` — index, create, edit.

## Reglas de negocio clave

1. **El gasto no tiene monto**: solo nombre + tipo + activo. El monto se asigna al facturar.
2. **`type`** es orientativo; la distribución real (`aliquota`/`equal`) se elige en el form
   de factura y se guarda en `InvoiceItem.distributed`.
3. **Solo `super_admin`** gestiona el catálogo.
4. Al facturar, solo se listan los items `active=true`.

## Casos de uso típicos
- Crear concepto "Vigilancia" (tipo aliquot) para repartir por alícuota.
- Crear concepto "Mantenimiento Ascensor" (tipo fixed) para cobro igualitario.
- Crear item inline desde el formulario de factura.

## Notas
- `type` no se valida estrictamente contra la distribución usada en `BillingService`.
- No hay soft deletes en `ExpenseItem`.