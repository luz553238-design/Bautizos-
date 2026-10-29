
@extends('crud.layout')

@section('title', 'Panel principal')

@section('crud_content')

@php
    $usuarioActual = auth()->user();

    $nombreUsuario = $usuarioActual->nombre_usuario ?? 'Usuario';

    /*
    |--------------------------------------------------------------------------
    | OBTENER ROLES DEL USUARIO
    |--------------------------------------------------------------------------
    */

    $rolesActuales = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
        ->where('ur.id_usuario', $usuarioActual->getAuthIdentifier())
        ->pluck('r.nombre')
        ->map(function ($rol) {
            return mb_strtolower(trim($rol));
        })
        ->all();

    $esSuperadmin = in_array(
        'superadministrador',
        $rolesActuales,
        true
    );

    $esSacerdote = in_array(
        'sacerdote',
        $rolesActuales,
        true
    );

    /*
    |--------------------------------------------------------------------------
    | PERMISO PARA REVISAR SOLICITUDES
    |--------------------------------------------------------------------------
    |
    | Solo sacerdotes y superadministradores.
    |
    */

    $puedeRevisarSolicitudes = $esSuperadmin || $esSacerdote;

    /*
    |--------------------------------------------------------------------------
    | OBTENER MÓDULOS PERMITIDOS
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | FILTRAR MÓDULOS DEL DASHBOARD
    |--------------------------------------------------------------------------
    */

    $modulosDashboard = array_filter(
        $modules,
        function ($definition, $name) use ($modulosPermitidos) {
            return in_array($name, $modulosPermitidos, true);
        },
        ARRAY_FILTER_USE_BOTH
    );

    /*
    |--------------------------------------------------------------------------
    | MÓDULOS PARA ESTADÍSTICAS
    |--------------------------------------------------------------------------
    */

    $modulosEstadisticas = [
        'personas',
        'solicitudes_bautismo',
        'citas',
        'bautismos'
    ];

    /*
    |--------------------------------------------------------------------------
    | PERMISO PARA CREAR SOLICITUDES DESDE EL CRUD
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | CONTAR SOLICITUDES PENDIENTES
    |--------------------------------------------------------------------------
    */

    $solicitudesPendientes = 0;

    if ($puedeRevisarSolicitudes) {

        $solicitudesPendientes = \Illuminate\Support\Facades\DB::table(
            'solicitudes_bautismo as s'
        )
            ->join(
                'estados_solicitud as e',
                'e.id_estado_solicitud',
                '=',
                's.id_estado_solicitud'
            )
            ->whereRaw(
                'LOWER(TRIM(e.nombre)) = ?',
                ['pendiente']
            )
            ->count();
    }
@endphp

<style>
    .revision-destacada {
        margin: 25px 0 35px;
        padding: 25px;
        border-radius: 16px;
        border: 1px solid #e4d5eb;
        background: linear-gradient(
            135deg,
            #f4eaf8,
            #ffffff
        );
    }

    .revision-contenido {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }

    .revision-informacion {
        flex: 1;
        min-width: 250px;
    }

    .revision-icono {
        font-size: 30px;
        color: #79527e;
        margin-bottom: 12px;
    }

    .revision-titulo {
        color: #352641;
        font-size: 23px;
        font-weight: 700;
        margin: 0 0 10px;
    }

    .revision-descripcion {
        color: #66586e;
        font-size: 14px;
        line-height: 1.7;
        margin: 0;
    }

    .revision-contador {
        display: inline-block;
        margin-top: 15px;
        padding: 8px 13px;
        border-radius: 20px;
        background: #fff0cf;
        color: #885c00;
        font-size: 13px;
        font-weight: 700;
    }

    .revision-boton {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        background: #79527e;
        color: white !important;
        padding: 14px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: background .2s;
    }

    .revision-boton:hover {
        background: #603e66;
        color: white;
    }

    @media (max-width: 700px) {
        .revision-destacada {
            padding: 20px;
        }

        .revision-boton {
            width: 100%;
        }
    }
</style>

<!-- ENCABEZADO PRINCIPAL -->

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

        @if(in_array('personas', $modulosPermitidos, true))

            <a
                class="dashboard-button dashboard-button-secondary"
                href="{{ route('crud.index', ['module' => 'personas']) }}"
            >
                <i class="bi bi-people"></i>
                Ver personas
            </a>

        @endif

        <!-- BOTÓN DE REVISIÓN DE BAUTISMOS -->

        @if($puedeRevisarSolicitudes)

            <a
                class="dashboard-button dashboard-button-secondary"
                href="{{ route('revision.index') }}"
            >
                <i class="bi bi-clipboard-check"></i>
                Revisar solicitudes
            </a>

        @endif

    </div>

</section>

<!-- ACCESO DESTACADO A REVISIÓN -->

@if($puedeRevisarSolicitudes)

    <section class="revision-destacada">

        <div class="revision-contenido">

            <div class="revision-informacion">

                <div class="revision-icono">
                    <i class="bi bi-clipboard2-check"></i>
                </div>

                <h2 class="revision-titulo">
                    Revisión de solicitudes de bautismo
                </h2>

                <p class="revision-descripcion">
                    Consulta las solicitudes registradas por los clientes,
                    revisa sus documentos y administra las autorizaciones
                    de las ceremonias de bautismo.
                </p>

                <span class="revision-contador">
                    {{ $solicitudesPendientes }}
                    {{ $solicitudesPendientes === 1
                        ? 'solicitud pendiente'
                        : 'solicitudes pendientes' }}
                </span>

            </div>

            <a
                href="{{ route('revision.index') }}"
                class="revision-boton"
            >
                <i class="bi bi-arrow-right-circle"></i>
                Abrir revisión
            </a>

        </div>

    </section>

@endif

<!-- ESTADÍSTICAS -->

<div class="dashboard-grid">

    @foreach($modulosEstadisticas as $key)

        @if(
            isset($modules[$key]) &&
            in_array($key, $modulosPermitidos, true)
        )

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

<!-- MÓDULOS DE ADMINISTRACIÓN -->

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
