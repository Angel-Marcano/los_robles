<?php
namespace App\Http\Controllers; use App\Models\{Apartment,Ownership,User}; use Illuminate\Http\Request;
class OwnershipController extends Controller {
    public function index(Apartment $apartment){ $this->authorize('viewAny',Ownership::class); $owners=Ownership::where('apartment_id',$apartment->id)->with('user')->get(); $users=User::orderBy('first_name')->get(); return view('ownerships.index',compact('apartment','owners','users')); }
    public function store(Request $r, Apartment $apartment){ $this->authorize('create',$apartment); $data=$r->validate(['user_id'=>'required|exists:tenant.users,id','role'=>'required|in:owner,co_owner,tenant']); Ownership::firstOrCreate(['apartment_id'=>$apartment->id,'user_id'=>$data['user_id']],['role'=>$data['role'],'active'=>true]); return redirect()->route('ownerships.index',$apartment)->with('status','Propietario asignado'); }
    public function destroy(Apartment $apartment, Ownership $ownership){ $this->authorize('delete',$ownership); if($ownership->apartment_id!=$apartment->id){ abort(404); } $ownership->delete(); return redirect()->route('ownerships.index',$apartment)->with('status','Registro eliminado'); }
    public function toggle(Apartment $apartment, Ownership $ownership){
        $this->authorize('toggle',$ownership);
        if($ownership->apartment_id!=$apartment->id){ abort(404); }
        $ownership->update(['active'=>!$ownership->active]);
        return back()->with('status','Propietario '.($ownership->active?'activado':'desactivado'));
    }
}
