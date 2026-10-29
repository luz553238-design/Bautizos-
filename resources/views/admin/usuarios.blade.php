
@extends('layouts.app')

@section('content')

<style>
    .usuarios-container {
        max-width: 1250px;
        margin: 25px auto;
        padding: 0 10px;
    }

    .usuarios-header {
        background: #f1e9f3;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 25px;
    }

    .usuarios-header h1 {
        color: #29203d;
        font-size: 32px;
        margin: 0 0 12px;
        font-weight: 700;
    }

    .usuarios-header p {
        color: #554861;
        margin: 0 0 20px;
        line-height: 1.6;
    }

    .usuarios-header a {
        color: #75409e;
        font-weight: 600;
        text-decoration: none;
    }

    .usuarios-header a:hover {
        text-decoration: underline;
    }

    .usuarios-card {
        background: white;
        border: 1px solid #e6dfe9;
        border-radius: 14px;
        padding: 20px;
        overflow-x: auto;
        box-shadow: 0 5px 20px rgba(65, 34, 92, 0.04);
    }

    .usuarios-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .usuarios-table th,
    .usuarios-table td {
        padding: 15px 13px;
        border-bottom: 1px solid #eee;
        text-align: left;
        vertical-align: middle;
        font-size: 14px;
    }

    .usuarios-table th {
        background: #f5eef6;
        color: #30233e;
        font-weight: 700;
    }

    .usuarios-table tbody tr:hover {
        background: #fcf9fd;
    }

    .usuarios-select {
        padding: 10px;
        border: 1px solid #d8c8dc;
        border-radius: 7px;
        min-width: 185px;
        background: #fff;
        color: #29203d;
        font-size: 14px;
    }

    .usuarios-select:focus {
        outline: none;
        border-color: #79527e;
        box-shadow: 0 0 0 3px rgba(121, 82, 126, 0.12);
    }

    .usuarios-form {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin: 0;
    }

    .usuarios-btn {
        background: #79527e;
        color: white;
        border: none;
        padding: 11px 16px;
        border-radius: 7px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.2s;
    }

    .usuarios-btn:hover {
        background: #603e66;
    }

    .usuarios-btn-danger {
        background: #a53b52;
    }

    .usuarios-btn-danger:hover {
        background: #842c40;
    }

    .usuarios-btn-success {
        background: #16803c;
    }

    .usuarios-btn-success:hover {
        background: #11642f;
    }

    .estado-activo {
        color: #16803c;
        font-weight: bold;
    }

    .estado-inactivo {
        color: #b42318;
        font-weight: bold;
    }

    .rol-protegido {
        display: inline-block;
        background: #efe3f5;
        color: #67378d;
        border-radius: 20px;
        padding: 8px 13px;
        font-weight: bold;
        font-size: 13px;
    }

    .texto-protegido {
        display: inline-block;
        color: #79527e;
        font-weight: 600;
        font-size: 13px;
    }

    .mensaje {
        padding: 15px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .mensaje-exito {
        background: #e4f7e9;
        color: #166534;
        border: 1px solid #bce6c6;
    }

    .mensaje-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .usuarios-nota {
        margin-top: 20px;
        padding: 15px;
        background: #f8f4fa;
        border-left: 4px solid #79527e;
        border-radius: 8px;
        color: #594766;
        font-size: 13px;
        line-height: 1.7;
    }

    .usuarios-vacio {
        padding: 30px;
        text-align: center;
        color: #786a84;
    }

    @media (max-width: 700px) {
        .usuarios-container {
            margin: 15px auto;
        }

        .usuarios-header {
            padding: 20px;
        }

        .usuarios-header h1 {
            font-size: 25px;
        }

        .usuarios-card {
            padding: 12px;
        }
    }
</style>

<div class="usuarios-container">

    <!-- ENCABEZADO -->

    <div class="usuarios-header">

        <h1>Administración de usuarios</h1>

        <p>
            Asigna roles y administra el estado
            de las cuentas registradas en el
            Sistema de Gestión de Bautizos.
        </p>

        <a href="{{ route('admin.permisos') }}">
            Configurar permisos
        </a>

    </div>

    <!-- MENSAJES -->

    @if(session('success'))

        <div class="mensaje mensaje-exito">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div class="mensaje mensaje-error">
            {{ session('error') }}
        </div>

    @endif

    @if($errors->any())

        <div class="mensaje mensaje-error">

            <strong>
                Se encontraron los siguientes errores:
            </strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif

    <!-- TABLA DE USUARIOS -->

    <div class="usuarios-card">

        <table class="usuarios-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                @forelse($usuarios as $usuario)

                    @php

                        // Roles asignados al usuario.

                        $rolesUsuario = array_map(
                            'intval',
                            $asignaciones[$usuario->id_usuario] ?? []
                        );

                        // Identificar el rol de superadministrador.

                        $superadmin = $roles->first(function ($rol) {
                            return mb_strtolower(
                                trim($rol->nombre)
                            ) === 'superadministrador';
                        });

                        $esSuperadmin = $superadmin
                            && in_array(
                                (int) $superadmin->id_rol,
                                $rolesUsuario,
                                true
                            );

                        // Identificar la cuenta autenticada.

                        $esCuentaActual =
                            (int) $usuario->id_usuario ===
                            (int) auth()->user()->id_usuario;

                        // Determinar si la cuenta está protegida.

                        $protegido = $esSuperadmin || $esCuentaActual;

                    @endphp

                    <tr>

                        <!-- ID -->

                        <td>
                            {{ $usuario->id_usuario }}
                        </td>

                        <!-- NOMBRE -->

                        <td>
                            {{ $usuario->nombre }}
                            {{ $usuario->apellido_paterno }}
                        </td>

                        <!-- USUARIO -->

                        <td>
                            {{ $usuario->nombre_usuario }}
                        </td>

                        <!-- ESTADO -->

                        <td>

                            @if($usuario->activo)

                                <span class="estado-activo">
                                    Activo
                                </span>

                            @else

                                <span class="estado-inactivo">
                                    Inactivo
                                </span>

                            @endif

                        </td>

                        <!-- ROL -->

                        <td>

                            @if($esSuperadmin)

                                <span class="rol-protegido">
                                    Superadministrador
                                </span>

                            @elseif($esCuentaActual)

                                <span class="texto-protegido">
                                    Cuenta actual protegida
                                </span>

                            @else

                                <form
                                    method="POST"
                                    action="{{ route('admin.usuarios.rol', $usuario->id_usuario) }}"
                                    class="usuarios-form"
                                    onsubmit="return confirmarCambioRol(this);"
                                >

                                    @csrf
                                    @method('PUT')

                                    <select
                                        name="id_rol"
                                        class="usuarios-select"
                                        required
                                        data-usuario="{{ $usuario->nombre_usuario }}"
                                    >

                                        @foreach($roles as $rol)

                                            <option
                                                value="{{ $rol->id_rol }}"
                                                data-nombre="{{ mb_strtolower(trim($rol->nombre)) }}"
                                                @selected(
                                                    in_array(
                                                        (int) $rol->id_rol,
                                                        $rolesUsuario,
                                                        true
                                                    )
                                                )
                                            >
                                                {{ ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $rol->nombre
                                                    )
                                                ) }}
                                            </option>

                                        @endforeach

                                    </select>

                                    <button
                                        type="submit"
                                        class="usuarios-btn"
                                    >
                                        Guardar
                                    </button>

                                </form>

                            @endif

                        </td>

                        <!-- ACCIONES -->

                        <td>

                            @if($protegido)

                                <span class="texto-protegido">
                                    Protegido
                                </span>

                            @else

                                <form
                                    method="POST"
                                    action="{{ route('admin.usuarios.estado', $usuario->id_usuario) }}"
                                    onsubmit="return confirm('¿Confirmas que deseas cambiar el estado de esta cuenta?');"
                                >

                                    @csrf
                                    @method('PATCH')

                                    @if($usuario->activo)

                                        <button
                                            type="submit"
                                            class="usuarios-btn usuarios-btn-danger"
                                        >
                                            Desactivar
                                        </button>

                                    @else

                                        <button
                                            type="submit"
                                            class="usuarios-btn usuarios-btn-success"
                                        >
                                            Activar
                                        </button>

                                    @endif

                                </form>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" class="usuarios-vacio">
                            No hay usuarios registrados.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <!-- INFORMACIÓN DE SEGURIDAD -->

    <div class="usuarios-nota">

        <strong>Seguridad de las cuentas:</strong>

        Solo los superadministradores pueden asignar roles,
        incluyendo el rol Superadministrador.

        Las cuentas que ya tienen ese rol están protegidas
        contra cambios de rol y desactivaciones desde este panel.

        Las cuentas desactivadas deben activarse antes de
        recibir el rol Superadministrador.

    </div>

</div>

<script>
    function confirmarCambioRol(formulario) {

        const selector = formulario.querySelector(
            'select[name="id_rol"]'
        );

        const opcion = selector.options[
            selector.selectedIndex
        ];

        const nombreRol = opcion.dataset.nombre || '';

        const usuario = selector.dataset.usuario || 'este usuario';

        if (nombreRol === 'superadministrador') {

            return confirm(
                'ATENCIÓN:\n\n' +
                'Estás a punto de convertir a "' +
                usuario +
                '" en SUPERADMINISTRADOR.\n\n' +
                'Este usuario tendrá acceso a la administración ' +
                'de cuentas, roles y permisos.\n\n' +
                'Una vez asignado, su cuenta quedará protegida ' +
                'contra cambios de rol y desactivación desde este panel.\n\n' +
                '¿Deseas continuar?'
            );
        }

        return confirm(
            '¿Deseas guardar el nuevo rol de "' +
            usuario +
            '"?'
        );
    }
</script>

@endsection
