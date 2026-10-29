<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    /**
     * Mostrar el panel principal del cliente.
     */
    public function dashboard()
    {
        $usuario = Auth::user();

        // Verificar que exista una sesion valida.
        if (!$usuario instanceof Usuario) {
            abort(401, 'Debes iniciar sesion.');
        }

        // Comprobar si tiene un rol de cliente.
        $esCliente = $usuario->roles()
            ->whereIn('roles.nombre', [
                'cliente',
                'padre_tutor',
                'padrino'
            ])
            ->exists();

        // Comprobar si tiene un rol administrativo.
        $esPersonal = $usuario->roles()
            ->whereIn('roles.nombre', [
                'superadministrador',
                'administrador',
                'secretaria',
                'sacerdote'
            ])
            ->exists();

        // El personal administrativo debe usar su propio panel.
        if ($esPersonal) {
            return redirect()->route('dashboard');
        }

        // Denegar acceso a usuarios sin rol autorizado.
        abort_unless(
            $esCliente,
            403,
            'No tienes acceso al panel de clientes.'
        );

        // Mostrar el panel del cliente.
        return view('cliente.dashboard', [
            'usuario' => $usuario
        ]);
    }
}
