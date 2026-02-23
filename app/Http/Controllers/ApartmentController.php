<?php
namespace App\Http\Controllers; 
use App\Models\{Apartment,Tower}; 
use Illuminate\Http\Request;

class ApartmentController extends Controller {
    public function index(Tower $tower){ 
        $apartments=$tower->apartments()->orderBy('code')->paginate(40); 
        \Log::info('Apartments index', [
            'tower_id'=>$tower->id,
            'count'=>$apartments->total(),
            'host'=>request()->getHost(),
        ]);
        return view('apartments.index',compact('apartments','tower')); 
    }
    public function create(Tower $tower){ 
        return view('apartments.create',compact('tower')); 
    }
    public function store(Request $r, Tower $tower){ 
        \Log::info('Apartment store request', [
            'tower_id' => $tower->id,
            'host' => $r->getHost(),
            'payload' => $r->all(),
        ]);
        $data=$r->validate([
            // Forzar la regla unique a usar la conexión tenant
            'code'=>'required|string|max:50|unique:tenant.apartments,code,NULL,id,tower_id,'.$tower->id,
            'aliquot_percent'=>'required|numeric|min:0.0001',
            'active'=>'nullable|boolean',
            // datos opcionales del propietario (usuario)
            'owner_name'    =>'nullable|string|max:120',
            'owner_email'   =>'nullable|email',
            'owner_password'=>'nullable|string|min:4'
        ]);
        $data['active']=$r->boolean('active');
        $apartment = $tower->apartments()->create($data);
        \Log::info('Apartment created', [
            'apartment_id' => $apartment->id,
            'tower_id' => $tower->id,
        ]);
        // Crear usuario propietario y asignar ownership si se proporcionó email
        if (!empty($data['owner_email'])) {
            $user = \App\Models\User::where('email',$data['owner_email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $data['owner_name'] ?: 'Propietario',
                    'email'=> $data['owner_email'],
                    'password' => bcrypt($data['owner_password'] ?: '1234'),
                    'active' => true,
                ]);
            }
            \App\Models\Ownership::firstOrCreate([
                'apartment_id' => $apartment->id,
                'user_id'      => $user->id,
            ]);
        }
        return redirect()->route('towers.apartments.index',$tower)->with('status','Apartamento creado'); 
    }
    public function edit(Apartment $apartment){ 
        $tower = $apartment->tower; 
        return view('apartments.edit',compact('apartment','tower')); 
    }
    public function update(Request $r, Apartment $apartment){ 
        $tower = $apartment->tower; 
        $data=$r->validate([
            'code'=>'required|string|max:50|unique:tenant.apartments,code,'.$apartment->id.',id,tower_id,'.$tower->id,
            'aliquot_percent'=>'required|numeric|min:0.0001',
            'active'=>'nullable|boolean',
            'owner_name'    =>'nullable|string|max:120',
            'owner_email'   =>'nullable|email',
            'owner_password'=>'nullable|string|min:4'
        ]);
        $data['active']=$r->boolean('active');
        $apartment->update($data);
        if (!empty($data['owner_email'])) {
            $user = \App\Models\User::where('email',$data['owner_email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $data['owner_name'] ?: 'Propietario',
                    'email'=> $data['owner_email'],
                    'password' => bcrypt($data['owner_password'] ?: '1234'),
                    'active' => true,
                ]);
            }
            \App\Models\Ownership::firstOrCreate([
                'apartment_id' => $apartment->id,
                'user_id'      => $user->id,
            ]);
        }
        return redirect()->route('towers.apartments.index',$tower)->with('status','Apartamento actualizado'); 
    }
    public function destroy(Apartment $apartment){ 
        $tower = $apartment->tower; 
        $apartment->delete(); 
        return redirect()->route('towers.apartments.index',$tower)->with('status','Apartamento eliminado'); 
    }
}
