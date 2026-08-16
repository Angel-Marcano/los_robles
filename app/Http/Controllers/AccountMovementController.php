<?php
namespace App\Http\Controllers; use App\Models\{Account,AccountMovement}; use Illuminate\Http\Request; use App\Services\AccountService; use Illuminate\Support\Facades\DB;
class AccountMovementController extends Controller {
    public function create(Account $account){ return view('accounts.movements.create',compact('account')); }
    public function store(Request $r, Account $account){
        $data=$r->validate([
            'type'=>'required|in:deposit,withdraw',
            'amount_usd'=>'nullable|numeric|min:0',
            'amount_ves'=>'nullable|numeric|min:0',
            'reference'=>'nullable|string|max:200'
        ]);
        DB::transaction(function() use($data,$account){
            $usd=$data['amount_usd']??0; $ves=$data['amount_ves']??0;
            if($data['type']==='deposit'){
                $account->increment('balance_usd',$usd);
                $account->increment('balance_ves',$ves);
            } else {
                if($usd>$account->balance_usd || $ves>$account->balance_ves){ abort(422,'Fondos insuficientes'); }
                $account->decrement('balance_usd',$usd);
                $account->decrement('balance_ves',$ves);
            }
            AccountMovement::create([
                'account_id'=>$account->id,
                'type'=>$data['type'],
                'amount_usd'=>$usd,
                'amount_ves'=>$ves,
                'reference'=>$data['reference']??null,
                'user_id'=>auth()->id(),
                'meta'=>[]
            ]);
        });
        return redirect()->route('accounts.index')->with('status','Movimiento registrado');
    }
    public function transferForm(){ $accounts=Account::orderBy('name')->get(); return view('accounts.movements.transfer',compact('accounts')); }
    public function transferStore(Request $r, \App\Services\AccountService $svc){ $data=$r->validate(['from_id'=>'required|different:to_id|exists:tenant.accounts,id','to_id'=>'required|exists:tenant.accounts,id','amount_usd'=>'nullable|numeric|min:0','amount_ves'=>'nullable|numeric|min:0','reference'=>'nullable|string|max:200']); $from=Account::findOrFail($data['from_id']); $to=Account::findOrFail($data['to_id']); $usd=$data['amount_usd']??0; $ves=$data['amount_ves']??0; if($usd<=0 && $ves<=0){ return back()->withErrors(['amount_usd'=>'Debe indicar al menos un monto > 0']); } if($usd>$from->balance_usd || $ves>$from->balance_ves){ return back()->withErrors(['from_id'=>'Fondos insuficientes en cuenta origen']); } $svc->moveFunds($from,$to,$usd,$ves,$data['reference']??null); return redirect()->route('accounts.index')->with('status','Transferencia realizada'); }
}
