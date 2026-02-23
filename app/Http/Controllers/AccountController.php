<?php
namespace App\Http\Controllers; use App\Models\Account; use Illuminate\Http\Request;
class AccountController extends Controller {
    public function index(){ $accounts=Account::orderBy('name')->paginate(30); return view('accounts.index',compact('accounts')); }
    public function create(){ return view('accounts.create'); }
    public function store(Request $r){
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'balance_usd' => 'nullable|numeric',
            'balance_ves' => 'nullable|numeric'
        ]);
        $acc = Account::create([
            'name' => $data['name'],
            'balance_usd' => $data['balance_usd'] ?? 0,
            'balance_ves' => $data['balance_ves'] ?? 0,
            'owner_type' => 'system',
            'owner_id' => 0
        ]);
        return redirect()->route('accounts.index')->with('status', 'Cuenta creada');
    }
    public function edit(Account $account){ return view('accounts.edit',compact('account')); }
    public function update(Request $r, Account $account){ $data=$r->validate(['name'=>'required|string|max:120']); $account->update(['name'=>$data['name']]); return redirect()->route('accounts.index')->with('status','Cuenta actualizada'); }
}
