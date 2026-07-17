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

Route::get('/', function () { return redirect()->route('login'); });
Route::view('/terminos', 'legal.terms')->name('legal.terms');
Route::get('/verify/invoice/{token}', [\App\Http\Controllers\VerifyInvoiceController::class, 'show'])->name('verify.invoice');
Route::get('/v/{token}', [\App\Http\Controllers\VerifyInvoiceController::class, 'show'])->name('verify.invoice.short');

// Chatbot
Route::middleware(['auth'])->prefix('chatbot')->group(function () {
    Route::post('/message', [\App\Http\Controllers\ChatbotController::class, 'message'])->name('chatbot.message');
    Route::get('/history', [\App\Http\Controllers\ChatbotController::class, 'history'])->name('chatbot.history');
});
Route::middleware(['auth'])->prefix('admin/chatbot')->group(function () {
    Route::get('/conversations', [\App\Http\Controllers\ChatbotAdminController::class, 'conversations'])->name('chatbot.admin.conversations');
    Route::get('/conversations/{conversation}', [\App\Http\Controllers\ChatbotAdminController::class, 'conversation'])->name('chatbot.admin.conversation');
    Route::patch('/conversations/{conversation}/resolve', [\App\Http\Controllers\ChatbotAdminController::class, 'resolve'])->name('chatbot.admin.resolve');
});

// Auth routes (minimal)
Route::get('login', [\App\Http\Controllers\Auth\LoginController::class,'showLogin'])->middleware('guest')->name('login');
Route::post('login', [\App\Http\Controllers\Auth\LoginController::class,'login'])->middleware(['guest','throttle:login'])->name('login.perform');
// Desafío 2FA (usuario aún no autenticado; estado en sesión)
Route::get('login/2fa', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class,'show'])->middleware('guest')->name('2fa.challenge');
Route::post('login/2fa', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class,'verify'])->middleware(['guest','throttle:login'])->name('2fa.verify');
Route::post('login/2fa/resend', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class,'resend'])->middleware(['guest','throttle:login'])->name('2fa.resend');
Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class,'logout'])->name('logout');
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
// Profile
Route::middleware(['auth'])->get('profile', [\App\Http\Controllers\ProfileController::class,'edit'])->name('profile.edit');
Route::middleware(['auth'])->patch('profile', [\App\Http\Controllers\ProfileController::class,'update'])->name('profile.update');
Route::middleware(['auth'])->patch('profile/password', [\App\Http\Controllers\ProfileController::class,'updatePassword'])->name('profile.password');
// Verificación en dos pasos (perfil)
Route::middleware(['auth'])->post('profile/2fa/email', [\App\Http\Controllers\TwoFactorSettingsController::class,'enableEmail'])->name('profile.2fa.email');
Route::middleware(['auth'])->post('profile/2fa/totp', [\App\Http\Controllers\TwoFactorSettingsController::class,'enableTotp'])->name('profile.2fa.totp');
Route::middleware(['auth'])->post('profile/2fa/confirm', [\App\Http\Controllers\TwoFactorSettingsController::class,'confirm'])->name('profile.2fa.confirm');
Route::middleware(['auth'])->post('profile/2fa/cancel', [\App\Http\Controllers\TwoFactorSettingsController::class,'cancel'])->name('profile.2fa.cancel');
Route::middleware(['auth'])->delete('profile/2fa', [\App\Http\Controllers\TwoFactorSettingsController::class,'disable'])->name('profile.2fa.disable');
// Users
Route::middleware(['auth'])->resource('users', \App\Http\Controllers\UserController::class);
Route::patch('users/{user}/toggle', [\App\Http\Controllers\UserController::class,'toggle'])->name('users.toggle');
// Condominiums
Route::middleware(['auth'])->resource('condominiums', \App\Http\Controllers\CondominiumController::class);
// Torres y Apartamentos (tenant context)
Route::middleware(['auth'])->resource('towers', \App\Http\Controllers\TowerController::class)->except(['show']);
Route::middleware(['auth'])->resource('towers.apartments', \App\Http\Controllers\ApartmentController::class)->shallow()->except(['show']);
Route::middleware(['auth'])->delete('towers/{tower}/apartments-bulk',  [\App\Http\Controllers\ApartmentController::class,'bulkDestroy'])->name('apartments.bulkDestroy');
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
// Anular factura aprobada/pagada
Route::middleware(['auth'])->patch('invoices/{invoice}/void', [\App\Http\Controllers\InvoiceController::class,'void'])->name('invoices.void');
// Reemitir factura aprobada/pagada (genera borrador clonado y marca original reemplazada)
Route::middleware(['auth'])->post('invoices/{invoice}/reissue', [\App\Http\Controllers\InvoiceController::class,'reissue'])->name('invoices.reissue');

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
// Fondo de reserva por torre
Route::middleware(['auth'])->get('reserve-funds',[\App\Http\Controllers\ReserveFundController::class,'index'])->name('reserve-funds.index');
Route::middleware(['auth'])->get('reserve-funds/{reserveFund}',[\App\Http\Controllers\ReserveFundController::class,'show'])->name('reserve-funds.show');
Route::middleware(['auth'])->get('reserve-funds/{reserveFund}/movements/create',[\App\Http\Controllers\ReserveFundController::class,'createMovement'])->name('reserve-funds.movements.create');
Route::middleware(['auth'])->post('reserve-funds/{reserveFund}/movements',[\App\Http\Controllers\ReserveFundController::class,'storeMovement'])->name('reserve-funds.movements.store');
// Password reset (sin auth)
Route::get('password/forgot',[\App\Http\Controllers\PasswordResetController::class,'showForgot']);
Route::post('password/forgot',[\App\Http\Controllers\PasswordResetController::class,'sendLink']);
Route::get('password/reset/{token}',[\App\Http\Controllers\PasswordResetController::class,'showReset']);
Route::post('password/reset',[\App\Http\Controllers\PasswordResetController::class,'performReset']);
// Auditoría
Route::middleware(['auth'])->get('audit-logs',[\App\Http\Controllers\AuditLogController::class,'index'])->name('audit.logs.index'); // export CSV via ?export=csv

// Reportes
Route::middleware(['auth'])->get('reports/debtors-monthly', [\App\Http\Controllers\ReportController::class, 'debtorsMonthly'])->name('reports.debtorsMonthly');
Route::middleware(['auth'])->get('reports/debtors-monthly/pdf', [\App\Http\Controllers\ReportController::class, 'debtorsMonthlyPdf'])->name('reports.debtorsMonthlyPdf');

