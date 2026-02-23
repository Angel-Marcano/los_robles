<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () { return view('welcome'); });
// Auth routes (minimal)
Route::get('login', [\App\Http\Controllers\Auth\LoginController::class,'showLogin'])->name('login');
Route::post('login', [\App\Http\Controllers\Auth\LoginController::class,'login'])->name('login.perform');
Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class,'logout'])->name('logout');
// Debug temporal de sesión y host (solo en entorno local)
if (env('APP_DEBUG')) {
    Route::get('debug/session', function(\Illuminate\Http\Request $r){
        return response()->json([
            'host' => $r->getHost(),
            'session_id' => $r->session()->getId(),
            'csrf_token' => csrf_token(),
            'user' => auth()->user()?->only(['id','email']),
            'cookie_session' => $_COOKIE[config('session.cookie')] ?? null,
        ]);
    });
    Route::get('debug/auth', function(\Illuminate\Http\Request $r){
        $tenantDb = config('database.connections.tenant.database') ?? null;
        // eager load roles para inspección
        $usersRaw = \App\Models\User::with('roles')->get();
        $users = $usersRaw->map(function($u){
            return [
                'id' => $u->id,
                'email' => $u->email,
                'active' => $u->active,
                'roles' => $u->roles->pluck('name'),
                'has_super_admin' => $u->hasRole('super_admin'),
                'created_at' => $u->created_at,
            ];
        });
        return response()->json([
            'host' => $r->getHost(),
            'tenant_db' => $tenantDb,
            'users' => $users,
            'count' => $users->count(),
        ]);
    });
}
Route::middleware(['auth'])->prefix('invoices')->group(function(){
    Route::get('', [\App\Http\Controllers\InvoiceController::class,'index'])->name('invoices.index');
    Route::get('create', [\App\Http\Controllers\InvoiceController::class,'create'])->name('invoices.create');
    Route::post('', [\App\Http\Controllers\InvoiceController::class,'store'])->name('invoices.store');
    Route::get('{invoice}', [\App\Http\Controllers\InvoiceController::class,'show'])->name('invoices.show');
    Route::get('{invoice}/edit', [\App\Http\Controllers\InvoiceController::class,'edit'])->name('invoices.edit');
    Route::patch('{invoice}', [\App\Http\Controllers\InvoiceController::class,'update'])->name('invoices.update');
    Route::get('{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class,'pdf'])->name('invoices.pdf');
    Route::patch('{invoice}/mark-paid', [\App\Http\Controllers\InvoiceController::class,'markPaid'])->name('invoices.markPaid');
    // Reportar pago de una factura específica
    Route::get('{invoice}/payments/create', [\App\Http\Controllers\PaymentReportController::class,'create'])->name('payments.create');
    Route::post('{invoice}/payments', [\App\Http\Controllers\PaymentReportController::class,'store'])->name('payments.store');
});
// Users
Route::middleware(['auth'])->resource('users', \App\Http\Controllers\UserController::class);
Route::patch('users/{user}/toggle', [\App\Http\Controllers\UserController::class,'toggle'])->name('users.toggle');
// Condominiums
Route::middleware(['auth'])->resource('condominiums', \App\Http\Controllers\CondominiumController::class);
// Torres y Apartamentos (tenant context)
Route::middleware(['auth'])->resource('towers', \App\Http\Controllers\TowerController::class)->except(['show']);
Route::middleware(['auth'])->resource('towers.apartments', \App\Http\Controllers\ApartmentController::class)->shallow()->except(['show']);
// Ownerships nested under apartment
Route::middleware(['auth'])->get('apartments/{apartment}/ownerships',[\App\Http\Controllers\OwnershipController::class,'index'])->name('ownerships.index');
Route::middleware(['auth'])->post('apartments/{apartment}/ownerships',[\App\Http\Controllers\OwnershipController::class,'store'])->name('ownerships.store');
Route::middleware(['auth'])->patch('apartments/{apartment}/ownerships/{ownership}/toggle',[\App\Http\Controllers\OwnershipController::class,'toggle'])->name('ownerships.toggle');
Route::middleware(['auth'])->delete('apartments/{apartment}/ownerships/{ownership}',[\App\Http\Controllers\OwnershipController::class,'destroy'])->name('ownerships.destroy');

// Expense Items (gastos comunes configurables)
Route::middleware(['auth'])->resource('expense-items', \App\Http\Controllers\ExpenseItemController::class)->except(['show']);
Route::middleware(['auth'])->post('expense-items/inline', [\App\Http\Controllers\ExpenseItemController::class,'storeInline'])->name('expense-items.inlineStore');

// Revisar / aprobar / rechazar reportes de pago
Route::middleware(['auth'])->get('payments/{paymentReport}/review', [\App\Http\Controllers\PaymentReportController::class,'review'])->name('payments.review');
Route::middleware(['auth'])->patch('payments/{paymentReport}/approve', [\App\Http\Controllers\PaymentReportController::class,'approve'])->name('payments.approve');
Route::middleware(['auth'])->patch('payments/{paymentReport}/reject', [\App\Http\Controllers\PaymentReportController::class,'reject'])->name('payments.reject');

// Aprobar factura (borrador -> pendiente)
Route::middleware(['auth'])->patch('invoices/{invoice}/approve', [\App\Http\Controllers\InvoiceController::class,'approve'])->name('invoices.approve');

// Tasas de cambio
Route::middleware(['auth'])->get('rates', [\App\Http\Controllers\CurrencyRateController::class,'index'])->name('rates.index');
Route::middleware(['auth'])->get('rates/create', [\App\Http\Controllers\CurrencyRateController::class,'create'])->name('rates.create');
Route::middleware(['auth'])->post('rates', [\App\Http\Controllers\CurrencyRateController::class,'store'])->name('rates.store');

// Cuentas y Movimientos
Route::middleware(['auth'])->get('accounts', [\App\Http\Controllers\AccountController::class,'index'])->name('accounts.index');
Route::middleware(['auth'])->get('accounts/create', [\App\Http\Controllers\AccountController::class,'create'])->name('accounts.create');
Route::middleware(['auth'])->post('accounts', [\App\Http\Controllers\AccountController::class,'store'])->name('accounts.store');
Route::middleware(['auth'])->get('accounts/{account}/edit', [\App\Http\Controllers\AccountController::class,'edit'])->name('accounts.edit');
Route::middleware(['auth'])->put('accounts/{account}', [\App\Http\Controllers\AccountController::class,'update'])->name('accounts.update');
Route::middleware(['auth'])->get('accounts/{account}/movements/create',[\App\Http\Controllers\AccountMovementController::class,'create'])->name('accounts.movements.create');
Route::middleware(['auth'])->post('accounts/{account}/movements',[\App\Http\Controllers\AccountMovementController::class,'store'])->name('accounts.movements.store');
Route::middleware(['auth'])->get('accounts/transfer',[\App\Http\Controllers\AccountMovementController::class,'transferForm'])->name('accounts.movements.transfer.form');
Route::middleware(['auth'])->post('accounts/transfer',[\App\Http\Controllers\AccountMovementController::class,'transferStore'])->name('accounts.movements.transfer.store');
// Exchange
Route::middleware(['auth'])->get('exchange/create',[\App\Http\Controllers\ExchangeTransactionController::class,'create'])->name('exchange.create');
Route::middleware(['auth'])->post('exchange',[\App\Http\Controllers\ExchangeTransactionController::class,'store'])->name('exchange.store');
// Password reset (sin auth)
Route::get('password/forgot',[\App\Http\Controllers\PasswordResetController::class,'showForgot']);
Route::post('password/forgot',[\App\Http\Controllers\PasswordResetController::class,'sendLink']);
Route::get('password/reset/{token}',[\App\Http\Controllers\PasswordResetController::class,'showReset']);
Route::post('password/reset',[\App\Http\Controllers\PasswordResetController::class,'performReset']);
// Auditoría
Route::middleware(['auth'])->get('audit-logs',[\App\Http\Controllers\AuditLogController::class,'index'])->name('audit.logs.index'); // export CSV via ?export=csv

