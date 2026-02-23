<?php
namespace App\Http\Controllers; 
use App\Models\Tower; 
use Illuminate\Http\Request;

class TowerController extends Controller {
    // En contexto tenant, todas las torres pertenecen al condominio implícito (DB actual)
    public function index(){ 
        $towers = Tower::orderBy('name')->paginate(30); 
        return view('towers.index',compact('towers')); 
    }
    public function create(){ 
        return view('towers.create'); 
    }
    public function store(Request $r){ 
        $data = $r->validate(['name'=>'required|string|max:120','active'=>'nullable|boolean']); 
        $data['active'] = $r->boolean('active');
        Tower::create($data); 
        return redirect()->route('towers.index'); 
    }
    public function edit(Tower $tower){ 
        return view('towers.edit',compact('tower')); 
    }
    public function update(Request $r, Tower $tower){ 
        $data=$r->validate(['name'=>'required|string|max:120','active'=>'nullable|boolean']); 
        $data['active']=$r->boolean('active');
        $tower->update($data); 
        return redirect()->route('towers.index'); 
    }
    public function destroy(Tower $tower){ 
        $tower->delete(); 
        return back(); 
    }
}
