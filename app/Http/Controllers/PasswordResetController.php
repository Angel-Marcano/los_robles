<?php
namespace App\Http\Controllers; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Hash; use Illuminate\Support\Facades\Mail; use Illuminate\Support\Str; use App\Models\User;
class PasswordResetController extends Controller {
    public function showForgot(){ return view('auth.forgot'); }
    public function sendLink(Request $r){ $data=$r->validate(['email'=>'required|email']); $user=User::where('email',$data['email'])->first(); if(!$user){ return back()->with('status','Si el correo existe recibirá un enlace'); }
        $token=Str::random(60); DB::table('password_resets')->updateOrInsert(['email'=>$user->email],['token'=>$token,'created_at'=>now()]);
        // Enviar correo simple
        Mail::raw("Recupera tu contraseña: ".url('/password/reset/'.$token), function($m) use($user){ $m->to($user->email)->subject('Recuperar contraseña'); });
        return back()->with('status','Enlace enviado'); }
    public function showReset($token){ return view('auth.reset',compact('token')); }
    public function performReset(Request $r){ $data=$r->validate(['token'=>'required','password'=>'required|min:6']); $record=DB::table('password_resets')->where('token',$data['token'])->first(); if(!$record){ return back()->withErrors(['token'=>'Token inválido']); } $user=User::where('email',$record->email)->first(); if(!$user){ return back()->withErrors(['email'=>'Usuario no encontrado']); }
        $user->update(['password'=>Hash::make($data['password'])]); DB::table('password_resets')->where('email',$record->email)->delete(); return redirect()->route('users.index')->with('status','Contraseña actualizada'); }
}
