<?php
use Illuminate\Support\Facades\Route; use Illuminate\Http\Request;
Route::post('login',[\App\Http\Controllers\Api\AuthController::class,'login']);
Route::middleware(['auth:sanctum','api.rate'])->group(function(){
    Route::get('me',[\App\Http\Controllers\Api\AuthController::class,'me']);
    Route::post('logout',[\App\Http\Controllers\Api\AuthController::class,'logout']);
    Route::get('invoices',[\App\Http\Controllers\Api\InvoiceApiController::class,'index']);
    Route::get('invoices/{invoice}',[\App\Http\Controllers\Api\InvoiceApiController::class,'show']);
    Route::get('accounts',[\App\Http\Controllers\Api\AccountApiController::class,'index']);
    Route::get('rates/current',[\App\Http\Controllers\Api\RateApiController::class,'current']);
});
