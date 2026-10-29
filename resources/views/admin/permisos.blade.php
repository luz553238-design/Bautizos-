
@extends('layouts.app')

@section('content')

<style>
    .admin-container {
        max-width: 1300px;
        margin: 25px auto;
    }

    .admin-header {
        background: linear-gradient(120deg, #efe6f1, #f8eef2);
        padding: 30px;
        border-radius: 18px;
        margin-bottom: 25px;
    }

    .admin-header h1 {
        color: #34243e;
        font-size: 30px;
        margin-bottom: 8px;
    }

    .admin-card {
        background: white;
        border: 1px solid #e7dfe9;
        border-radius: 15px;
        padding: 22px;
        margin-bottom: 25px;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        text-align: center;
    }

    .admin-table th {
        background: #f4edf5;
        color: #46334d;
    }

    .admin-table td:first-child {
        text-align: left;
        font-weight: 600;
    }

    .admin-table input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #79527e;
        cursor: pointer;
    }

    .admin-btn {
        display: inline-block;
        background: #79527e;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 11px 22px;
        cursor: pointer;
        text-decoration: none;
    }

    .admin-btn:hover {
        background: #5f3c65;
        color: white;
    }

    .admin-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .admin-tabs button {
        border: 1px solid #d8c7dc;
        background: white;
        border-radius: 8px;
        padding: 10px 16px;
        cursor: pointer;
    }

    .admin-tabs button.active {
        background: #79527e;
        color: white;
    }

    .admin-role-panel {
        display: none;
    }

    .admin-role-panel.active {
        display: block;
    }
</style>

<div class="admin-container">

    <div class="admin-header">
        <h1>Administración de permisos</h1>

        <p>
            Configura los permisos de cada rol
            del Sistema de Gestión de Bautizos.
        </p>

        <a
            href="{{ route('admin.usuarios') }}"
            class="admin-btn"
        >
            Administrar usuarios
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-tabs">

        @foreach($roles as $rol)

            @if($rol->nombre !== 'superadministrador')

                <button
                    type="button"
                    class="{{ $loop->first ? 'active' : '' }}"
                    onclick="mostrarRol({{ $rol->id_rol }}, this)"
                >
                    {{ ucfirst(str_replace('_', ' ', $rol->nombre)) }}
                </button>

            @endif

        @endforeach

    </div>

    @foreach($roles as $rol)

        @if($rol->nombre !== 'superadministrador')

            <div
                id="rol-{{ $rol->id_rol }}"
                class="admin-role-panel admin-card"
            >

                <h2>
                    Permisos de:
                    {{ ucfirst(str_replace('_', ' ', $rol->nombre)) }}
                </h2>

                <p>
                    Marca las acciones que este rol
                    puede realizar.
                </p>

                <form
                    method="POST"
                    action="{{ route('admin.permisos.actualizar', $rol->id_rol) }}"
                >
                    @csrf
                    @method('PUT')

                    <div style="overflow-x:auto;">

                        <table class="admin-table">

                            <thead>
                                <tr>
                                    <th>Módulo</th>
                                    <th>Ver</th>
                                    <th>Crear</th>
                                    <th>Editar</th>
                                    <th>Eliminar</th>
                                </tr>
                            </thead>

                            <tbody>

                                @php
                                    $agrupados = $permisos->groupBy('modulo');

                                    $protegidos = [
                                        'usuarios',
                                        'roles',
                                        'usuario_rol',
                                        'permisos',
                                        'rol_permiso'
                                    ];
                                @endphp

                                @foreach($agrupados as $modulo => $acciones)

                                    @if(!in_array($modulo, $protegidos))

                                        <tr>

                                            <td>
                                                {{ ucfirst(str_replace('_', ' ', $modulo)) }}
                                            </td>

                                            @foreach(['ver', 'crear', 'editar', 'eliminar'] as $accion)

                                                @php
                                                    $permiso = $acciones->firstWhere(
                                                        'accion',
                                                        $accion
                                                    );

                                                    $seleccionado = $permiso &&
                                                        in_array(
                                                            (int) $permiso->id_permiso,
                                                            $asignaciones[$rol->id_rol] ?? []
                                                        );
                                                @endphp

                                                <td>

                                                    @if($permiso)

                                                        <input
                                                            type="checkbox"
                                                            name="permisos[]"
                                                            value="{{ $permiso->id_permiso }}"
                                                            {{ $seleccionado ? 'checked' : '' }}
                                                        >

                                                    @else

                                                        —

                                                    @endif

                                                </td>

                                            @endforeach

                                        </tr>

                                    @endif

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                    <div style="margin-top:20px;text-align:right;">

                        <button
                            type="submit"
                            class="admin-btn"
                        >
                            Guardar permisos
                        </button>

                    </div>

                </form>

            </div>

        @endif

    @endforeach

</div>

<script>
    function mostrarRol(id, boton) {

        document.querySelectorAll('.admin-role-panel')
            .forEach(panel => {
                panel.classList.remove('active');
            });

        document.querySelectorAll('.admin-tabs button')
            .forEach(tab => {
                tab.classList.remove('active');
            });

        document.getElementById('rol-' + id)
            .classList.add('active');

        boton.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function () {

        const primerPanel = document.querySelector(
            '.admin-role-panel'
        );

        const primerBoton = document.querySelector(
            '.admin-tabs button'
        );

        if (primerPanel) {
            primerPanel.classList.add('active');
        }

        if (primerBoton) {
            primerBoton.classList.add('active');
        }

    });
</script>

@endsection
