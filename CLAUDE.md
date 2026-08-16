# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Laravel 10 (PHP ^8.1, MySQL) multi-tenant SaaS for condominium administration ("Los Robles"). Code comments, UI, and README are in Spanish. See `README.md` for the full domain/architecture write-up (in Spanish).

## Commands

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan serve                      # dev server; APP_URL is a tenant subdomain (e.g. condo_demo.test)
npm install && npm run dev             # laravel-mix (webpack.mix.js); npm run watch / prod

php artisan test                       # or vendor/bin/phpunit
php artisan test --filter=ApiInvoiceTest                    # single class
php artisan test --filter=test_invoices_index_paginates_and_filters   # single method

# Tenancy
php artisan migrate                                          # landlord DB (database/migrations)
php artisan tenants:create {name} {subdomain} [--db=] [--seed] # register condominium, create its DB, run tenant migrations
php artisan tenants:migrate [--fresh] [--seed] [--tenant=ID]   # run database/migrations/tenant on all/selected tenants
php artisan tenants:seed-roles / tenants:seed-admins [--password=]
php artisan make:migration add_x_to_y --path=database/migrations/tenant   # tenant schema changes go here
```

Docker: `Dockerfile` is a php:8.2-cli dev image (no compose, no DB) — run MySQL separately.

Tests use MySQL (sqlite lines in `phpunit.xml` are commented out) and there is no tenant bootstrap in `tests/TestCase.php`; several Feature tests still reference legacy `condominium_id` columns. Don't assume the suite is green before touching it.

## Architecture: subdomain → dedicated tenant DB

- **Landlord DB** (`mysql` connection): `condominiums` table (`subdomain`, `db_name`, `active`) plus Laravel plumbing.
- **Tenant DB** (`tenant` connection): everything else — towers, apartments, expense_items, invoices, invoice_items, accounts, account_movements, exchange_transactions, ownerships, payment_reports, currency_rates, audit_logs, **users**, and spatie permission tables. Schema lives in `database/migrations/tenant/` (base migration `create_domain_tables` + incrementals).
- **`App\Http\Middleware\IdentifyCondominium`** is *global* middleware (`app/Http/Kernel.php`): takes the first host label as `subdomain`, loads `Condominium`, binds `currentCondominium` / `currentCondominiumId` in the container, then rewrites `config('database.connections.tenant')` to `db_name` and purges/reconnects. Unknown host → 404. Every request (web, api, tests) therefore needs a matching `condominiums` row.
- **`App\Models\Traits\UsesTenantConnection`** (on all domain models incl. `User`): `getConnectionName()` returns `tenant` when `currentCondominium` is bound, else the default. Tenant context is implicit — do **not** add `condominium_id` to tenant tables or filter by it. `BelongsToCondominium` trait + `condominium_id` on `Account` are legacy remnants of the earlier single-DB design.
- **Roles/permissions**: spatie/laravel-permission with tenant-scoped models `App\Models\Tenant\Role` / `Permission` (`$connection = 'tenant'`, wired in `config/permission.php`). Roles: `super_admin`, `condo_admin` (full tenant access), `tower_admin` (scoped to own tower). Policies in `app/Policies`.
- **Services** (`app/Services`): `BillingService::generateInvoice(period, expenseItemIds, apartmentIds, lateFee, towerId)` — `fixed` items replicate the amount per apartment, `aliquot` items prorate across apartments (or by `apartments.aliquot_percent`); totals in USD and VES via `CurrencyService` / `currency_rates`; queues `InvoiceCreatedMail` to owners via `ownerships`. `AccountService` handles account movements/transfers; `AuditService` writes `audit_logs` (also fed by `InvoiceObserver` / `PaymentReportObserver`).
- **HTTP**: `routes/web.php` — Blade CRUD (`resources/views/*`), all behind `auth`. `routes/api.php` — `auth:sanctum` + custom `api.rate` middleware (`ApiRateLimit`), controllers in `app/Http/Controllers/Api` sharing `Concerns/ApiResponses`. PDF via dompdf; CSV exports stream with `chunk()`.

## Conventions

- New tenant table/column → incremental migration under `database/migrations/tenant/`, then `php artisan tenants:migrate`. Never edit an already-applied tenant migration.
- No physical FKs from tenant tables to anything outside the tenant DB.
- Avoid landlord models (`Condominium`) inside tenant logic; avoid raw queries on the `mysql` connection for domain data.
