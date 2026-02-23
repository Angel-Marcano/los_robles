<?php
namespace App\Http\Controllers; use App\Models\Condominium; use Illuminate\Http\Request;
class CondominiumController extends Controller {
    public function index(){ $items=Condominium::paginate(20); return view('condominiums.index',compact('items')); }
    public function create(){ return view('condominiums.create'); }
    public function store(Request $r){ $data=$r->validate(['name'=>'required|string|max:120']); Condominium::create($data); return redirect()->route('condominiums.index'); }
    public function show(Condominium $condominium){ return view('condominiums.show',compact('condominium')); }
    public function edit(Condominium $condominium){ return view('condominiums.edit',compact('condominium')); }
    public function update(Request $r, Condominium $condominium){ $data=$r->validate(['name'=>'required|string|max:120','active'=>'sometimes|boolean']); $condominium->update($data); return redirect()->route('condominiums.index'); }
    public function destroy(Condominium $condominium){ $condominium->delete(); return redirect()->route('condominiums.index'); }
}
