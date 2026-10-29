
@extends('layouts.app')

@section('content')

<style>
    .usuarios-container {
        max-width: 1250px;
        margin: 25px auto;
    }

    .usuarios-header {
        background: #f1e9f3;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 25px;
    }

    .usuarios-card {
        background: white;
        border: 1px solid #e6dfe9;
        border-radius: 14px;
        padding: 20px;
        overflow-x: auto;
    }

    .usuarios-table {
        width: 100%;
        border-collapse: collapse;
    }

    .usuarios-table th,
    .usuarios-table td {
        padding: 13px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }

    .usuarios-table th {
        background: #f5eef6;
    }

    .usuarios-select {
        padding: 8px;
        border: 1px solid #d8c8dc;
        border-radius: 7px;
        min-width: 150px;
    }

    .usuarios-btn {
        background: #79527e;
        color: white;
        border: none;
        padding: 9px 15px;
        border-radius: 7px;
        cursor: pointer;
    }

    .usuarios-btn:hover {
        background: #603e66;
    }

    .estado-activo {
        color: #16803c;
        font-weight: bold;
    }

    .estado-inactivo {
        color: #b42318;
        font-weight: bold;
    }
</style>

<div class="usuarios-container">

    <div class="usuarios-header">

        <h1>Administración de usuarios</h1>

        <p>
            Asigna roles y administra el estado
            de las cuentas registradas.
        </p>

        <a href="{{ route('admin.permisos') }}">
            Configurar permisos
        </a>

    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

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

                @foreach($usuarios as $usuario)

                    @php
                        $rolesUsuario = $asignaciones[$usuario->id_usuario] ?? [];

                        $superadmin = $roles->firstWhere(
                            'nombre',
                            'superadministrador'
                        );

                        $esSuperadmin = $superadmin &&
                            in_array(
                                (int) $superadmin->id_rol,
                                $rolesUsuario
                            );
                    @endphp

                    <tr>

                        <td>
                            {{ $usuario->id_usuario }}
                        </td>

                        <td>
                            {{ $usuario->nombre }}
                            {{ $usuario->apellido_paterno }}
                        </td>

                        <td>
                            {{ $usuario->nombre_usuario }}
                        </td>

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

                        <td>

                            @if($esSuperadmin)

                                Superadministrador

                            @else

                                <form
                                    method="POST"
                                    action="{{ route('admin.usuarios.rol', $usuario->id_usuario) }}"
                                >
                                    @csrf
                                    @method('PUT')

                                    <select
                                        name="id_rol"
                                        class="usuarios-select"
                                        required
                                    >

                                        @foreach($roles as $rol)

                                            @if($rol->nombre !== 'superadministrador')

                                                <option
                                                    value="{{ $rol->id_rol }}"
                                                    {{ in_array((int) $rol->id_rol, $rolesUsuario) ? 'selected' : '' }}
                                                >
                                                    {{ ucfirst(str_replace('_', ' ', $rol->nombre)) }}
                                                </option>

                                            @endif

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

                        <td>

                            @if(!$esSuperadmin)

                                <form
                                    method="POST"
                                    action="{{ route('admin.usuarios.estado', $usuario->id_usuario) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="usuarios-btn"
                                    >
                                        {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                    </button>

                                </form>

                            @else

                                Protegido

                            @endif

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection
