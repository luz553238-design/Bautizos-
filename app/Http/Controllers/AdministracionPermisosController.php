<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdministracionPermisosController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | AUTORIZACION DEL SUPERADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    /**
     * Verificar que el usuario autenticado
     * tenga el rol de superadministrador.
     */
    private function autorizar(): void
    {
        $usuario = Auth::user();

        // Comprobar que el usuario inicio sesion.
        if (!$usuario instanceof Usuario) {
            abort(401, 'Debes iniciar sesion.');
        }

        // Comprobar que la cuenta este activa.
        abort_unless(
            $usuario->activo,
            403,
            'Tu cuenta esta desactivada.'
        );

        // Consultar el rol directamente desde MySQL.
        $esSuperadmin = DB::table('usuario_rol as ur')
            ->join(
                'roles as r',
                'ur.id_rol',
                '=',
                'r.id_rol'
            )
            ->where(
                'ur.id_usuario',
                $usuario->id_usuario
            )
            ->where(
                'r.nombre',
                'superadministrador'
            )
            ->exists();

        // Impedir acceso a usuarios sin autorizacion.
        abort_unless(
            $esSuperadmin,
            403,
            'Solo el superadministrador puede administrar usuarios, roles y permisos.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MOSTRAR MATRIZ DE PERMISOS
    |--------------------------------------------------------------------------
    */

    /**
     * Mostrar todos los roles, permisos
     * y asignaciones existentes.
     */
    public function index()
    {
        $this->autorizar();

        // Obtener los roles registrados.
        $roles = DB::table('roles')
            ->orderBy('nombre')
            ->get();

        // Obtener los permisos disponibles.
        $permisos = DB::table('permisos')
            ->orderBy('modulo')
            ->orderBy('accion')
            ->get();

        // Obtener permisos asignados a cada rol.
        $asignaciones = DB::table('rol_permiso')
            ->get()
            ->groupBy('id_rol')
            ->map(function ($items) {
                return $items
                    ->pluck('id_permiso')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all();
            })
            ->all();

        // Mostrar la vista de permisos.
        return view(
            'admin.permisos',
            compact(
                'roles',
                'permisos',
                'asignaciones'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR PERMISOS DE UN ROL
    |--------------------------------------------------------------------------
    */

    /**
     * Guardar los permisos seleccionados
     * para un rol determinado.
     */
    public function actualizarPermisos(
        Request $request,
        int $idRol
    ) {
        $this->autorizar();

        // Buscar el rol.
        $rol = DB::table('roles')
            ->where('id_rol', $idRol)
            ->first();

        abort_unless(
            $rol,
            404,
            'El rol solicitado no existe.'
        );

        // No permitir modificar los permisos
        // del superadministrador.
        abort_if(
            $rol->nombre === 'superadministrador',
            403,
            'No se pueden modificar los permisos del superadministrador.'
        );

        // Validar los permisos recibidos.
        $data = $request->validate([
            'permisos' => [
                'nullable',
                'array'
            ],
            'permisos.*' => [
                'integer',
                'distinct',
                Rule::exists(
                    'permisos',
                    'id_permiso'
                )
            ]
        ]);

        // Convertir los identificadores a enteros.
        $ids = array_map(
            'intval',
            $data['permisos'] ?? []
        );

        // Modulos reservados exclusivamente
        // para el superadministrador.
        $modulosProtegidos = [
            'usuarios',
            'roles',
            'usuario_rol',
            'permisos',
            'rol_permiso'
        ];

        // Comprobar si se intentan asignar
        // permisos administrativos protegidos.
        $contieneProtegidos = !empty($ids)
            && DB::table('permisos')
                ->whereIn('id_permiso', $ids)
                ->whereIn(
                    'modulo',
                    $modulosProtegidos
                )
                ->exists();

        abort_if(
            $contieneProtegidos,
            403,
            'No puedes asignar permisos de seguridad a este rol.'
        );

        // Actualizar los permisos dentro
        // de una transaccion.
        DB::transaction(function () use (
            $idRol,
            $ids
        ) {

            // Eliminar asignaciones anteriores.
            DB::table('rol_permiso')
                ->where('id_rol', $idRol)
                ->delete();

            // Registrar los nuevos permisos.
            if (!empty($ids)) {

                $registros = array_map(
                    function ($id) use ($idRol) {
                        return [
                            'id_rol' => $idRol,
                            'id_permiso' => $id
                        ];
                    },
                    $ids
                );

                DB::table('rol_permiso')
                    ->insert($registros);
            }
        });

        return redirect()
            ->route('admin.permisos')
            ->with(
                'success',
                'Los permisos del rol fueron actualizados correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | MOSTRAR USUARIOS
    |--------------------------------------------------------------------------
    */

    /**
     * Mostrar todos los usuarios registrados
     * y los roles asignados.
     */
    public function usuarios()
    {
        $this->autorizar();

        // Obtener usuarios y datos personales.
        $usuarios = DB::table('usuarios as u')
            ->join(
                'personas as p',
                'u.id_persona',
                '=',
                'p.id_persona'
            )
            ->select(
                'u.id_usuario',
                'u.nombre_usuario',
                'u.activo',
                'p.nombre',
                'p.apellido_paterno'
            )
            ->orderBy('u.id_usuario')
            ->get();

        // Obtener todos los roles.
        $roles = DB::table('roles')
            ->orderBy('nombre')
            ->get();

        // Obtener los roles de cada usuario.
        $asignaciones = DB::table('usuario_rol')
            ->get()
            ->groupBy('id_usuario')
            ->map(function ($items) {
                return $items
                    ->pluck('id_rol')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all();
            })
            ->all();

        return view(
            'admin.usuarios',
            compact(
                'usuarios',
                'roles',
                'asignaciones'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNAR ROL A UN USUARIO
    |--------------------------------------------------------------------------
    */

    /**
     * Reemplazar el rol principal
     * de un usuario registrado.
     */
    public function actualizarRol(
        Request $request,
        int $idUsuario
    ) {
        $this->autorizar();

        // Verificar que el usuario exista.
        $usuario = DB::table('usuarios')
            ->where('id_usuario', $idUsuario)
            ->first();

        abort_unless(
            $usuario,
            404,
            'El usuario solicitado no existe.'
        );

        // Validar el rol seleccionado.
        $data = $request->validate([
            'id_rol' => [
                'required',
                'integer',
                Rule::exists(
                    'roles',
                    'id_rol'
                )
            ]
        ]);

        // Obtener el nuevo rol.
        $rolNuevo = DB::table('roles')
            ->where(
                'id_rol',
                $data['id_rol']
            )
            ->first();

        // Comprobar si el usuario actualmente
        // es superadministrador.
        $esSuperadmin = DB::table('usuario_rol as ur')
            ->join(
                'roles as r',
                'ur.id_rol',
                '=',
                'r.id_rol'
            )
            ->where(
                'ur.id_usuario',
                $idUsuario
            )
            ->where(
                'r.nombre',
                'superadministrador'
            )
            ->exists();

        // Proteger los usuarios superadministradores.
        abort_if(
            $esSuperadmin,
            403,
            'No se puede reemplazar el rol de un superadministrador.'
        );

        // Impedir asignar el rol superadministrador
        // desde este formulario.
        abort_if(
            $rolNuevo->nombre === 'superadministrador',
            403,
            'La asignacion del rol superadministrador esta protegida.'
        );

        // Actualizar el rol dentro de una transaccion.
        DB::transaction(function () use (
            $idUsuario,
            $data
        ) {

            // Eliminar los roles anteriores.
            DB::table('usuario_rol')
                ->where(
                    'id_usuario',
                    $idUsuario
                )
                ->delete();

            // Asignar el nuevo rol.
            DB::table('usuario_rol')
                ->insert([
                    'id_usuario' => $idUsuario,
                    'id_rol' => $data['id_rol']
                ]);
        });

        return redirect()
            ->route('admin.usuarios')
            ->with(
                'success',
                'El rol del usuario fue actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVAR O DESACTIVAR USUARIOS
    |--------------------------------------------------------------------------
    */

    /**
     * Cambiar el estado de una cuenta
     * sin eliminar sus registros.
     */
    public function cambiarEstado(
        int $idUsuario
    ) {
        $this->autorizar();

        // Buscar usuario.
        $usuario = DB::table('usuarios')
            ->where(
                'id_usuario',
                $idUsuario
            )
            ->first();

        abort_unless(
            $usuario,
            404,
            'El usuario solicitado no existe.'
        );

        // Verificar si es superadministrador.
        $esSuperadmin = DB::table('usuario_rol as ur')
            ->join(
                'roles as r',
                'ur.id_rol',
                '=',
                'r.id_rol'
            )
            ->where(
                'ur.id_usuario',
                $idUsuario
            )
            ->where(
                'r.nombre',
                'superadministrador'
            )
            ->exists();

        // No permitir desactivar superadministradores.
        abort_if(
            $esSuperadmin,
            403,
            'No se puede modificar el estado de un superadministrador.'
        );

        // Invertir el estado actual.
        $nuevoEstado = !$usuario->activo;

        DB::table('usuarios')
            ->where(
                'id_usuario',
                $idUsuario
            )
            ->update([
                'activo' => $nuevoEstado
            ]);

        return redirect()
            ->route('admin.usuarios')
            ->with(
                'success',
                $nuevoEstado
                    ? 'El usuario fue activado correctamente.'
                    : 'El usuario fue desactivado correctamente.'
            );
    }
}
