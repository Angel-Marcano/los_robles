<?php
namespace App\Services; use App\Models\CurrencyRate; use Carbon\Carbon;
class CurrencyService { public function currentRate():?CurrencyRate {return CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();} public function setRate(float $rate):CurrencyRate {CurrencyRate::where('active',true)->update(['active'=>false]); return CurrencyRate::create(['base'=>'USD','quote'=>'VES','rate'=>$rate,'valid_from'=>Carbon::now(),'active'=>true]);}}
