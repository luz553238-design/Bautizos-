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
    | AUTORIZACIÓN DEL SUPERADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    private function autorizar(): void
    {
        $usuario = Auth::user();

        if (!$usuario instanceof Usuario) {
            abort(401, 'Debes iniciar sesión.');
        }

        abort_unless(
            (bool) $usuario->activo,
            403,
            'Tu cuenta está desactivada.'
        );

        $esSuperadmin = DB::table('usuario_rol as ur')
            ->join(
                'roles as r',
                'ur.id_rol',
                '=',
                'r.id_rol'
            )
            ->where('ur.id_usuario', $usuario->id_usuario)
            ->whereRaw(
                'LOWER(TRIM(r.nombre)) = ?',
                ['superadministrador']
            )
            ->exists();

        abort_unless(
            $esSuperadmin,
            403,
            'Solo el superadministrador puede administrar usuarios, roles y permisos.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR SI UN USUARIO ES SUPERADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    private function esSuperadministrador(int $idUsuario): bool
    {
        return DB::table('usuario_rol as ur')
            ->join(
                'roles as r',
                'ur.id_rol',
                '=',
                'r.id_rol'
            )
            ->where('ur.id_usuario', $idUsuario)
            ->whereRaw(
                'LOWER(TRIM(r.nombre)) = ?',
                ['superadministrador']
            )
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | MOSTRAR MATRIZ DE PERMISOS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $this->autorizar();

        $roles = DB::table('roles')
            ->orderBy('nombre')
            ->get();

        $permisos = DB::table('permisos')
            ->orderBy('modulo')
            ->orderBy('accion')
            ->get();

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

    public function actualizarPermisos(
        Request $request,
        int $idRol
    ) {
        $this->autorizar();

        $rol = DB::table('roles')
            ->where('id_rol', $idRol)
            ->first();

        abort_unless(
            $rol,
            404,
            'El rol solicitado no existe.'
        );

        // Los permisos del superadministrador son protegidos.

        abort_if(
            mb_strtolower(trim($rol->nombre)) === 'superadministrador',
            403,
            'No se pueden modificar los permisos del superadministrador.'
        );

        $data = $request->validate([
            'permisos' => [
                'nullable',
                'array'
            ],
            'permisos.*' => [
                'integer',
                'distinct',
                Rule::exists('permisos', 'id_permiso')
            ]
        ]);

        $ids = array_map(
            'intval',
            $data['permisos'] ?? []
        );

        // Módulos reservados para superadministradores.

        $modulosProtegidos = [
            'usuarios',
            'roles',
            'usuario_rol',
            'permisos',
            'rol_permiso'
        ];

        $contieneProtegidos = !empty($ids)
            && DB::table('permisos')
                ->whereIn('id_permiso', $ids)
                ->whereIn('modulo', $modulosProtegidos)
                ->exists();

        abort_if(
            $contieneProtegidos,
            403,
            'No puedes asignar permisos de seguridad a este rol.'
        );

        DB::transaction(function () use ($idRol, $ids) {

            DB::table('rol_permiso')
                ->where('id_rol', $idRol)
                ->delete();

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

    public function usuarios()
    {
        $this->autorizar();

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

        // Incluimos todos los roles,
        // también el de superadministrador.

        $roles = DB::table('roles')
            ->orderBy('nombre')
            ->get();

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
    |
    | Reglas:
    |
    | 1. Solo un superadministrador puede cambiar roles.
    | 2. Se permite asignar el rol superadministrador.
    | 3. Los superadministradores existentes están protegidos.
    | 4. No se permite modificar el propio rol.
    |
    */

    public function actualizarRol(
        Request $request,
        int $idUsuario
    ) {
        $this->autorizar();

        $data = $request->validate([
            'id_rol' => [
                'required',
                'integer',
                Rule::exists('roles', 'id_rol')
            ]
        ]);

        DB::transaction(function () use ($idUsuario, $data) {

            // Bloquear el registro durante el cambio.

            $usuario = DB::table('usuarios')
                ->where('id_usuario', $idUsuario)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $usuario,
                404,
                'El usuario solicitado no existe.'
            );

            // Proteger la cuenta autenticada.

            abort_if(
                $idUsuario === (int) Auth::user()->id_usuario,
                403,
                'No puedes modificar tu propio rol.'
            );

            // Proteger los superadministradores existentes.

            abort_if(
                $this->esSuperadministrador($idUsuario),
                403,
                'No se puede reemplazar el rol de un superadministrador.'
            );

            $rolNuevo = DB::table('roles')
                ->where('id_rol', $data['id_rol'])
                ->first();

            abort_unless(
                $rolNuevo,
                404,
                'El rol seleccionado no existe.'
            );

            // Un superadministrador autorizado sí puede
            // asignar el rol de superadministrador.

            // Evitar asignar privilegios a cuentas desactivadas.

            if (
                mb_strtolower(trim($rolNuevo->nombre)) === 'superadministrador'
            ) {
                abort_unless(
                    (bool) $usuario->activo,
                    422,
                    'Debes activar la cuenta antes de convertirla en superadministrador.'
                );
            }

            // Reemplazar el rol anterior.

            DB::table('usuario_rol')
                ->where('id_usuario', $idUsuario)
                ->delete();

            DB::table('usuario_rol')
                ->insert([
                    'id_usuario' => $idUsuario,
                    'id_rol' => (int) $data['id_rol']
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

    public function cambiarEstado(int $idUsuario)
    {
        $this->autorizar();

        $nuevoEstado = DB::transaction(function () use ($idUsuario) {

            $usuario = DB::table('usuarios')
                ->where('id_usuario', $idUsuario)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $usuario,
                404,
                'El usuario solicitado no existe.'
            );

            // Impedir que un usuario se desactive a sí mismo.

            abort_if(
                $idUsuario === (int) Auth::user()->id_usuario,
                403,
                'No puedes modificar el estado de tu propia cuenta.'
            );

            // Proteger todas las cuentas superadministradoras.

            abort_if(
                $this->esSuperadministrador($idUsuario),
                403,
                'No se puede modificar el estado de un superadministrador.'
            );

            $nuevoEstado = !(bool) $usuario->activo;

            DB::table('usuarios')
                ->where('id_usuario', $idUsuario)
                ->update([
                    'activo' => $nuevoEstado
                ]);

            return $nuevoEstado;
        });

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
