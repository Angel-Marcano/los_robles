<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User; use App\Http\Controllers\Api\Concerns\ApiResponses;

class AuthController extends Controller {
    use ApiResponses;
    protected function userPayload(User $user): array {
        // Devuelve datos básicos + roles y permisos para consumo del cliente
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => method_exists($user,'getRoleNames') ? $user->getRoleNames() : [],
            'permissions' => method_exists($user,'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [],
        ];
    }
    public function login(Request $r){
        $data=$r->validate(['email'=>'required|email','password'=>'required']);
        $user=User::where('email',$data['email'])->first();
        if(!$user || !Hash::check($data['password'],$user->password)){
            return $this->errorResponse('INVALID_CREDENTIALS', __('errors.INVALID_CREDENTIALS'), 401);
        }
        $token=$user->createToken('api')->plainTextToken;
        return response()->json(['token'=>$token,'user'=>$this->userPayload($user)]);
    }
    public function me(Request $r){ return response()->json(['user'=>$this->userPayload($r->user())]); }
    public function logout(Request $r){ $r->user()->currentAccessToken()->delete(); return response()->json(['data'=>['ok'=>true]]); }
}
