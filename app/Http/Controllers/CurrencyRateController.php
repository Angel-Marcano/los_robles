<?php
namespace App\Http\Controllers; use App\Models\CurrencyRate; use Illuminate\Http\Request; use App\Services\CurrencyService;
class CurrencyRateController extends Controller { public function index(){ $rates=CurrencyRate::orderByDesc('valid_from')->paginate(30); return view('rates.index',compact('rates')); } public function create(){ return view('rates.create'); } public function store(Request $r, CurrencyService $svc){ $data=$r->validate(['rate'=>'required|numeric|min:0']); $svc->setRate($data['rate']); return redirect()->route('rates.index'); }}
