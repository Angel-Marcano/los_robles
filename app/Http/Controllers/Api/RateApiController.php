<?php
namespace App\Http\Controllers\Api; use App\Http\Controllers\Controller; use App\Models\CurrencyRate;
class RateApiController extends Controller { public function current(){ $rate=CurrencyRate::where('active',true)->orderByDesc('valid_from')->first(); return response()->json(['data'=>$rate]); } }
