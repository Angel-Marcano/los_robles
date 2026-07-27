# Módulo: Reportes (Morosidad)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Reportes administrativos. Actualmente: **morosidad mensual** por apartamento (saldo
pendiente por mes del año) con exportación a PDF.

## Controlador: `ReportController`

### `debtorsMonthly(Request)`
- Año seleccionable (default año actual; validado 2000–2100).
- Consulta facturas `status='pending'` con `apartment_id` no null y `period` LIKE `{year}-%`.
- Eager load: `apartment.tower`, `apartment.ownerships` (active, con user),
  `paymentReports` (status `approved` o null — compatibilidad con registros viejos).
- Por cada factura:
  - Calcula `paidUsdEq` (suma de `usdEquivalent()` de reportes).
  - `remaining = max(0, dueUsdEquivalent - paidUsdEq)`.
  - Si `remaining <= 0`, se omite.
  - Acumula por apartamento y por mes.
- Construye filas: `apartment_code`, `tower_name`, `owner_name`, `monthly[1..12]`, `total`.
- Ordena por `total` desc; redondea a 2 decimales.
- Retorna vista `reports.debtors_monthly`.

### `debtorsMonthlyPdf(Request)`
- Reutiliza la misma lógica de datos y genera PDF (Dompdf).

## Rutas
- `reports.debtors-monthly`, `reports.debtors-monthly.pdf` (en `routes/web.php`).

## Vistas
- `resources/views/reports/` — `debtors_monthly.blade.php` (+ versión PDF).

## Reglas de negocio clave

1. **Solo facturas `pending`** con apartamento (sub-facturas) se consideran morosas.
2. **Saldo pendiente** = total + mora − abonos aprobados (USD equivalente).
3. **Compatibilidad**: reportes viejos sin `status` se cuentan como aprobados.
4. Agrupación por apartamento y mes del `period` (YYYY-MM).
5. Ordenado por mayor deuda total.

## Casos de uso típicos
- Ver morosidad del año por apartamento y mes.
- Exportar PDF para junta de condominio.
- Identificar apartamentos con mayor deuda acumulada.

## Notas / deudas
- Solo existe el reporte de morosidad mensual. Pendiente: reportes de ingresos, flujo de
  caja, fondo de reserva, etc.
- No hay policy explícita — acceso por rol (admin).