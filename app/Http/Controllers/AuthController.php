<?php
namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form() { return view('auth.login', ['initial' => false]); }
    public function registration() { return view('auth.login', ['initial' => true]); }
    public function register(Request $request) {
        $data=$request->validate(['nombre'=>'required|string|max:100','apellido_paterno'=>'required|string|max:100','fecha_nacimiento'=>'required|date|before_or_equal:today','sexo'=>'required|in:masculino,femenino,otro','nombre_usuario'=>'required|string|max:50|unique:usuarios,nombre_usuario','password'=>'required|string|min:8|confirmed']);
        DB::transaction(function() use ($data) {
            $id=DB::table('personas')->insertGetId(array_intersect_key($data,array_flip(['nombre','apellido_paterno','fecha_nacimiento','sexo'])));
            Usuario::create(['id_persona'=>$id,'nombre_usuario'=>$data['nombre_usuario'],'contrasena'=>Hash::make($data['password']),'activo'=>false]);
        });
        return redirect()->route('login')->with('success','Cuenta creada. Solicita a la administración que la active para iniciar sesión.');
    }
    public function login(Request $request) {
        $data = $request->validate(['nombre_usuario'=>'required|string|max:50','password'=>'required|string']);
        $key = 'login:'.hash('sha256', mb_strtolower($data['nombre_usuario']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['nombre_usuario'=>'Demasiados intentos. Espera '.RateLimiter::availableIn($key).' segundos.']);
        if (!Auth::attempt(['nombre_usuario'=>$data['nombre_usuario'],'password'=>$data['password'],'activo'=>1])) {
            RateLimiter::hit($key,60);
            throw ValidationException::withMessages(['nombre_usuario'=>'El usuario o la contraseña no son correctos, o la cuenta está inactiva.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::user()->forceFill(['ultimo_acceso'=>now()])->save();
        return redirect()->route('dashboard');
    }
    public function setup(Request $request) {
        abort_if(Usuario::exists(),403);
        $data=$request->validate(['nombre'=>'required|string|max:100','apellido_paterno'=>'required|string|max:100','fecha_nacimiento'=>'required|date|before_or_equal:today','sexo'=>'required|in:masculino,femenino,otro','nombre_usuario'=>'required|string|max:50|unique:usuarios,nombre_usuario','password'=>'required|string|min:8|confirmed']);
        $user=DB::transaction(function() use ($data) {
            abort_if(Usuario::exists(),403);
            $id=DB::table('personas')->insertGetId(array_intersect_key($data,array_flip(['nombre','apellido_paterno','fecha_nacimiento','sexo'])));
            return Usuario::create(['id_persona'=>$id,'nombre_usuario'=>$data['nombre_usuario'],'contrasena'=>Hash::make($data['password']),'activo'=>true]);
        });
        Auth::login($user); $request->session()->regenerate();
        return redirect()->route('dashboard');
    }
    public function logout(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login')->with('success','Cerraste sesión correctamente.');
    }
}
