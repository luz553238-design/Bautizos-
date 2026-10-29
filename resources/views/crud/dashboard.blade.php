
@extends('crud.layout')

@section('title', 'Panel principal')

@section('crud_content')

@php
    $usuarioActual = auth()->user();

    $nombreUsuario = $usuarioActual->nombre_usuario ?? 'Usuario';

    $rolesActuales = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
        ->where('ur.id_usuario', $usuarioActual->getAuthIdentifier())
        ->pluck('r.nombre')
        ->all();

    $esSuperadmin = in_array('superadministrador', $rolesActuales);

    $modulosPermitidos = [];

    if ($esSuperadmin) {
        $modulosPermitidos = array_keys($modules);
    } else {
        $modulosPermitidos = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
            ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
            ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
            ->where('ur.id_usuario', $usuarioActual->getAuthIdentifier())
            ->where('p.accion', 'ver')
            ->distinct()
            ->pluck('p.modulo')
            ->all();

        $modulosPermitidos = array_values(
            array_diff($modulosPermitidos, [
                'usuarios',
                'roles',
                'usuario_rol'
            ])
        );
    }

    $modulosDashboard = array_filter(
        $modules,
        function ($definition, $name) use ($modulosPermitidos) {
            return in_array($name, $modulosPermitidos);
        },
        ARRAY_FILTER_USE_BOTH
    );

    $modulosEstadisticas = [
        'personas',
        'solicitudes_bautismo',
        'citas',
        'bautismos'
    ];

    $puedeCrearSolicitud = $esSuperadmin;

    if (!$puedeCrearSolicitud) {
        $puedeCrearSolicitud = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
            ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
            ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
            ->where('ur.id_usuario', $usuarioActual->getAuthIdentifier())
            ->where('p.modulo', 'solicitudes_bautismo')
            ->where('p.accion', 'crear')
            ->exists();
    }
@endphp

<section class="hero">

    <div class="eyebrow">
        Menú principal
    </div>

    <h1>
        Bienvenido, {{ $nombreUsuario }}.
    </h1>

    <p>
        Administra las personas, organiza solicitudes,
        consulta las citas y acompaña cada etapa
        del proceso de bautismo desde un solo lugar.
    </p>

    <div class="dashboard-actions">

        @if($puedeCrearSolicitud)
            <a
                class="dashboard-button dashboard-button-primary"
                href="{{ route('crud.create', ['module' => 'solicitudes_bautismo']) }}"
            >
                <i class="bi bi-plus-circle"></i>
                Nueva solicitud
            </a>
        @endif

        @if(in_array('personas', $modulosPermitidos))
            <a
                class="dashboard-button dashboard-button-secondary"
                href="{{ route('crud.index', ['module' => 'personas']) }}"
            >
                <i class="bi bi-people"></i>
                Ver personas
            </a>
        @endif

    </div>

</section>

<div class="dashboard-grid">

    @foreach($modulosEstadisticas as $key)

        @if(isset($modules[$key]) && in_array($key, $modulosPermitidos))

            <div class="dashboard-stat">

                <div class="dashboard-stat-label">
                    {{ $modules[$key]['title'] }}
                </div>

                <strong class="dashboard-stat-number">
                    {{ $counts[$key] ?? 0 }}
                </strong>

                <div style="color:#9993a4;font-size:11px;margin-top:8px;">
                    Registros en el sistema
                </div>

            </div>

        @endif

    @endforeach

</div>

<h2 class="dashboard-section-title">
    <i class="bi bi-grid"></i>
    Módulos de administración
</h2>

<div class="dashboard-modules">

    @forelse($modulosDashboard as $name => $definition)

        <a
            class="module-link"
            href="{{ route('crud.index', ['module' => $name]) }}"
        >

            <div class="dashboard-module">

                <div style="color:#795e89;font-size:24px;">
                    <i class="bi bi-folder2-open"></i>
                </div>

                <p style="margin-top:16px;">
                    {{ $counts[$name] ?? 0 }} registros
                </p>

                <h3>
                    {{ $definition['title'] }}
                </h3>

                <p>
                    Consultar y administrar registros
                    de este módulo.
                </p>

                <div style="color:#795e89;font-weight:700;font-size:12px;margin-top:18px;">
                    Abrir módulo
                    <i class="bi bi-arrow-right"></i>
                </div>

            </div>

        </a>

    @empty

        <div class="dashboard-module">
            <h3>Sin módulos disponibles</h3>
            <p>
                Actualmente no tienes módulos de administración
                asignados a tu rol.
            </p>
        </div>

    @endforelse

</div>

@endsection
