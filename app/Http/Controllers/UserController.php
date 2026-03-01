<?php
namespace App\Http\Controllers; use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Hash;
class UserController extends Controller {
    public function index(){ $users=User::paginate(10); return view('users.index',compact('users')); }
    public function create(){ return view('users.create'); }
    public function store(Request $r){ $data=$r->validate(['name'=>'required','first_name'=>'required|string|max:80','last_name'=>'required|string|max:120','document_type'=>'required|in:cedula,pasaporte','document_number'=>'required|string|max:40','email'=>'required|email|unique:users','password'=>'required|min:6']); $data['password']=Hash::make($data['password']); User::create($data); return redirect()->route('users.index'); }
    public function edit(User $user){ return view('users.edit',compact('user')); }
    public function update(Request $r, User $user){
        // Permitir actualización parcial (ej. documento desde vista de apartamentos)
        $data=$r->validate([
            'name'=>'sometimes|string|max:120',
            'first_name'=>'sometimes|string|max:80',
            'last_name'=>'sometimes|string|max:120',
            'document_type'=>'sometimes|nullable|in:cedula,pasaporte',
            'document_number'=>'sometimes|nullable|string|max:40',
            'email'=>'sometimes|email|unique:users,email,'.$user->id,
            'password'=>'sometimes|nullable|min:6',
            'active'=>'sometimes|boolean'
        ]);
        if(array_key_exists('password',$data) && !empty($data['password'])){
            $data['password']=Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if(array_key_exists('active',$data)){
            $data['active']=(bool)$data['active'];
        }
        $user->update($data);
        return back()->with('status','Documento actualizado');
    }
    public function destroy(User $user){ $user->delete(); return back(); }
    public function toggle(User $user){ $user->update(['active'=>!$user->active]); return back(); }
}
