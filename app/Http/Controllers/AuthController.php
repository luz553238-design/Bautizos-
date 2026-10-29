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
    /**
     * Mostrar formulario de inicio de sesion.
     */
    public function form()
    {
        return view('auth.login', [
            'initial' => false
        ]);
    }

    /**
     * Mostrar formulario de registro.
     */
    public function registration()
    {
        return view('auth.login', [
            'initial' => true
        ]);
    }

    /**
     * Registrar un nuevo usuario con rol cliente.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'sexo' => 'required|in:masculino,femenino,otro',
            'nombre_usuario' => 'required|string|max:50|unique:usuarios,nombre_usuario',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($data) {

            // Buscar el rol cliente.
            $rolCliente = DB::table('roles')
                ->where('nombre', 'cliente')
                ->first();

            if (!$rolCliente) {
                throw ValidationException::withMessages([
                    'nombre_usuario' =>
                        'El rol cliente no esta configurado. Contacta al administrador.'
                ]);
            }

            // Preparar datos de la persona.
            $datosPersona = array_intersect_key(
                $data,
                array_flip([
                    'nombre',
                    'apellido_paterno',
                    'fecha_nacimiento',
                    'sexo'
                ])
            );

            // Registrar persona.
            $idPersona = DB::table('personas')
                ->insertGetId($datosPersona);

            // Crear cuenta activa.
            $usuario = Usuario::create([
                'id_persona' => $idPersona,
                'nombre_usuario' => $data['nombre_usuario'],
                'contrasena' => Hash::make($data['password']),
                'activo' => true,
            ]);

            // Asignar automaticamente el rol cliente.
            DB::table('usuario_rol')->insert([
                'id_usuario' => $usuario->id_usuario,
                'id_rol' => $rolCliente->id_rol,
            ]);
        });

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Cuenta creada correctamente. Ya puedes iniciar sesion como cliente.'
            );
    }

    /**
     * Iniciar sesion.
     */
    public function login(Request $request)
    {
        // Validar credenciales.
        $data = $request->validate([
            'nombre_usuario' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        // Identificador para limitar intentos.
        $key = 'login:' . hash(
            'sha256',
            mb_strtolower($data['nombre_usuario']) .
            '|' .
            $request->ip()
        );

        // Comprobar limite de intentos.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'nombre_usuario' =>
                    'Demasiados intentos. Espera ' .
                    RateLimiter::availableIn($key) .
                    ' segundos.',
            ]);
        }

        // Credenciales y estado de la cuenta.
        $credenciales = [
            'nombre_usuario' => $data['nombre_usuario'],
            'password' => $data['password'],
            'activo' => 1,
        ];

        // Intentar iniciar sesion.
        if (!Auth::attempt($credenciales)) {

            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'nombre_usuario' =>
                    'El usuario o la contraseña no son correctos, o la cuenta esta inactiva.',
            ]);
        }

        // Limpiar intentos fallidos.
        RateLimiter::clear($key);

        // Regenerar la sesion.
        $request->session()->regenerate();

        // Obtener el usuario autenticado.
        $usuario = Auth::user();

        // Registrar el ultimo acceso.
        if ($usuario instanceof Usuario) {
            $usuario->ultimo_acceso = now();
            $usuario->save();
        }

        // Enviar al panel correspondiente segun el rol.
        return redirect()->route('panel.redirect');
    }

    /**
     * Crear el primer superadministrador.
     *
     * Solo funciona cuando no existen usuarios.
     */
    public function setup(Request $request)
    {
        // Impedir crear otro superadministrador inicial.
        abort_if(Usuario::exists(), 403);

        // Validar informacion.
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'sexo' => 'required|in:masculino,femenino,otro',
            'nombre_usuario' => 'required|string|max:50|unique:usuarios,nombre_usuario',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Crear el primer usuario dentro de una transaccion.
        $usuario = DB::transaction(function () use ($data) {

            // Volver a comprobar que no existan usuarios.
            abort_if(Usuario::exists(), 403);

            // Buscar rol superadministrador.
            $rolSuperadmin = DB::table('roles')
                ->where('nombre', 'superadministrador')
                ->first();

            if (!$rolSuperadmin) {
                throw ValidationException::withMessages([
                    'nombre_usuario' =>
                        'El rol superadministrador no existe.'
                ]);
            }

            // Preparar datos personales.
            $datosPersona = array_intersect_key(
                $data,
                array_flip([
                    'nombre',
                    'apellido_paterno',
                    'fecha_nacimiento',
                    'sexo'
                ])
            );

            // Crear persona.
            $idPersona = DB::table('personas')
                ->insertGetId($datosPersona);

            // Crear usuario superadministrador.
            $nuevoUsuario = Usuario::create([
                'id_persona' => $idPersona,
                'nombre_usuario' => $data['nombre_usuario'],
                'contrasena' => Hash::make($data['password']),
                'activo' => true,
            ]);

            // Asignar rol superadministrador.
            DB::table('usuario_rol')->insert([
                'id_usuario' => $nuevoUsuario->id_usuario,
                'id_rol' => $rolSuperadmin->id_rol,
            ]);

            return $nuevoUsuario;
        });

        // Iniciar sesion del superadministrador.
        Auth::login($usuario);

        // Regenerar sesion.
        $request->session()->regenerate();

        // Redirigir al panel administrativo.
        return redirect()->route('panel.redirect');
    }

    /**
     * Cerrar sesion.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidar sesion actual.
        $request->session()->invalidate();

        // Regenerar token CSRF.
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Cerraste sesion correctamente.'
            );
    }
}
