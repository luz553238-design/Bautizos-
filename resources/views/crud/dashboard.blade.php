
@extends('crud.layout')

@section('title', 'Panel principal')

@section('crud_content')

@php
    /*
    |--------------------------------------------------------------------------
    | USUARIO AUTENTICADO
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | IDENTIFICAR ROLES
    |--------------------------------------------------------------------------
    */

    $esSuperadmin = in_array(
        'superadministrador',
        $rolesActuales,
        true
    );

    $esAdministrador = in_array(
        'administrador',
        $rolesActuales,
        true
    );

    /*
    |--------------------------------------------------------------------------
    | PERMISOS ADMINISTRATIVOS
    |--------------------------------------------------------------------------
    */

    $puedeRevisarSolicitudes = $esSuperadmin || $esAdministrador;

    $puedeRegistrarBautismos = $esSuperadmin || $esAdministrador;

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
                'usuario_rol',
                'rol_permiso',
                'permisos'
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

    /*
    |--------------------------------------------------------------------------
    | CONTAR BAUTISMOS REGISTRADOS
    |--------------------------------------------------------------------------
    */

    $bautismosRegistrados = 0;

    if ($puedeRegistrarBautismos) {

        $bautismosRegistrados = \Illuminate\Support\Facades\DB::table(
            'bautismos'
        )->count();
    }

    /*
    |--------------------------------------------------------------------------
    | CONTAR SOLICITUDES APROBADAS SIN BAUTISMO
    |--------------------------------------------------------------------------
    */

    $bautismosPendientesRegistro = 0;

    if ($puedeRegistrarBautismos) {

        $bautismosPendientesRegistro = \Illuminate\Support\Facades\DB::table(
            'solicitudes_bautismo as s'
        )
            ->join(
                'estados_solicitud as e',
                'e.id_estado_solicitud',
                '=',
                's.id_estado_solicitud'
            )
            ->join(
                'citas as c',
                'c.id_solicitud',
                '=',
                's.id_solicitud'
            )
            ->join(
                'horarios_bautizo as h',
                'h.id_horario',
                '=',
                'c.id_horario'
            )
            ->leftJoin(
                'bautismos as b',
                'b.id_cita',
                '=',
                'c.id_cita'
            )
            ->whereRaw(
                'LOWER(TRIM(e.nombre)) IN (?, ?)',
                ['aprobada', 'autorizada']
            )
            ->whereNull('b.id_bautismo')
            ->whereIn('c.estado', [
                'programada',
                'confirmada',
                'reprogramada'
            ])
            ->whereDate('h.fecha', '<=', now()->toDateString())
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPROBAR DISPONIBILIDAD DE RUTAS
    |--------------------------------------------------------------------------
    */

    $rutaRevisionDisponible = \Illuminate\Support\Facades\Route::has(
        'revision.index'
    );

    $rutaBautismosDisponible = \Illuminate\Support\Facades\Route::has(
        'bautismos.index'
    );

@endphp

<style>

    /*
    |--------------------------------------------------------------------------
    | TARJETAS DESTACADAS
    |--------------------------------------------------------------------------
    */

    .panel-destacado {
        margin: 25px 0;
        padding: 25px;
        border-radius: 16px;
        border: 1px solid #e4d5eb;
        background: linear-gradient(
            135deg,
            #f4eaf8,
            #ffffff
        );
        box-shadow: 0 5px 20px rgba(65, 34, 92, 0.04);
    }

    .panel-destacado-bautismos {
        background: linear-gradient(
            135deg,
            #edf2ff,
            #ffffff
        );
        border-color: #dce2f5;
    }

    .panel-destacado-contenido {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }

    .panel-destacado-informacion {
        flex: 1;
        min-width: 250px;
    }

    .panel-destacado-icono {
        font-size: 30px;
        color: #79527e;
        margin-bottom: 12px;
    }

    .panel-destacado-titulo {
        color: #352641;
        font-size: 23px;
        font-weight: 700;
        margin: 0 0 10px;
    }

    .panel-destacado-descripcion {
        color: #66586e;
        font-size: 14px;
        line-height: 1.7;
        margin: 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CONTADORES
    |--------------------------------------------------------------------------
    */

    .panel-contadores {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .panel-contador {
        display: inline-block;
        padding: 8px 13px;
        border-radius: 20px;
        background: #fff0cf;
        color: #885c00;
        font-size: 13px;
        font-weight: 700;
    }

    .panel-contador-exito {
        background: #e4f7e9;
        color: #166534;
    }

    .panel-contador-informacion {
        background: #e8e9ff;
        color: #4644a2;
    }

    /*
    |--------------------------------------------------------------------------
    | BOTONES
    |--------------------------------------------------------------------------
    */

    .panel-destacado-boton {
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
        white-space: nowrap;
    }

    .panel-destacado-boton:hover {
        background: #603e66;
        color: white;
    }

    .panel-destacado-boton-azul {
        background: #5752a3;
    }

    .panel-destacado-boton-azul:hover {
        background: #403c87;
    }

    /*
    |--------------------------------------------------------------------------
    | ADAPTACIÓN A PANTALLAS PEQUEÑAS
    |--------------------------------------------------------------------------
    */

    @media (max-width: 700px) {

        .panel-destacado {
            padding: 20px;
        }

        .panel-destacado-titulo {
            font-size: 20px;
        }

        .panel-destacado-boton {
            width: 100%;
        }

        .panel-destacado-informacion {
            min-width: 100%;
        }

    }

</style>

<!-- ========================================================= -->
<!-- ENCABEZADO PRINCIPAL -->
<!-- ========================================================= -->

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

    <!-- BOTONES PRINCIPALES -->

    <div class="dashboard-actions">

        <!-- CREAR SOLICITUD -->

        @if($puedeCrearSolicitud)

            <a
                class="dashboard-button dashboard-button-primary"
                href="{{ route('crud.create', ['module' => 'solicitudes_bautismo']) }}"
            >
                <i class="bi bi-plus-circle"></i>
                Nueva solicitud
            </a>

        @endif

        <!-- VER PERSONAS -->

        @if(in_array('personas', $modulosPermitidos, true))

            <a
                class="dashboard-button dashboard-button-secondary"
                href="{{ route('crud.index', ['module' => 'personas']) }}"
            >
                <i class="bi bi-people"></i>
                Ver personas
            </a>

        @endif

        <!-- REVISAR SOLICITUDES -->

        @if($puedeRevisarSolicitudes && $rutaRevisionDisponible)

            <a
                class="dashboard-button dashboard-button-secondary"
                href="{{ route('revision.index') }}"
            >
                <i class="bi bi-clipboard-check"></i>
                Revisar solicitudes
            </a>

        @endif

        <!-- REGISTRAR BAUTISMOS -->

        @if($puedeRegistrarBautismos && $rutaBautismosDisponible)

            <a
                class="dashboard-button dashboard-button-secondary"
                href="{{ route('bautismos.index') }}"
            >
                <i class="bi bi-journal-check"></i>
                Registrar bautismos
            </a>

        @endif

    </div>

</section>

<!-- ========================================================= -->
<!-- REVISIÓN DE SOLICITUDES -->
<!-- ========================================================= -->

@if($puedeRevisarSolicitudes && $rutaRevisionDisponible)

    <section class="panel-destacado">

        <div class="panel-destacado-contenido">

            <div class="panel-destacado-informacion">

                <div class="panel-destacado-icono">
                    <i class="bi bi-clipboard2-check"></i>
                </div>

                <h2 class="panel-destacado-titulo">
                    Revisión de solicitudes de bautismo
                </h2>

                <p class="panel-destacado-descripcion">
                    Consulta las solicitudes registradas por los clientes,
                    revisa sus documentos y administra las autorizaciones
                    de las ceremonias de bautismo.
                </p>

                <div class="panel-contadores">

                    <span class="panel-contador">
                        {{ $solicitudesPendientes }}
                        {{ $solicitudesPendientes === 1
                            ? 'solicitud pendiente'
                            : 'solicitudes pendientes' }}
                    </span>

                </div>

            </div>

            <a
                href="{{ route('revision.index') }}"
                class="panel-destacado-boton"
            >
                <i class="bi bi-arrow-right-circle"></i>
                Abrir revisión
            </a>

        </div>

    </section>

@endif

<!-- ========================================================= -->
<!-- REGISTRO DE BAUTISMOS -->
<!-- ========================================================= -->

@if($puedeRegistrarBautismos && $rutaBautismosDisponible)

    <section class="panel-destacado panel-destacado-bautismos">

        <div class="panel-destacado-contenido">

            <div class="panel-destacado-informacion">

                <div class="panel-destacado-icono">
                    <i class="bi bi-journal-check"></i>
                </div>

                <h2 class="panel-destacado-titulo">
                    Registro de bautismos realizados
                </h2>

                <p class="panel-destacado-descripcion">
                    Registra las ceremonias de bautismo que ya se
                    realizaron, asigna el folio del acta,
                    número de libro, página y observaciones.
                    Consulta los bautismos registrados.
                </p>

                <div class="panel-contadores">

                    <span class="panel-contador">
                        {{ $bautismosPendientesRegistro }}
                        {{ $bautismosPendientesRegistro === 1
                            ? 'bautismo pendiente de registro'
                            : 'bautismos pendientes de registro' }}
                    </span>

                    <span class="panel-contador panel-contador-exito">
                        {{ $bautismosRegistrados }}
                        {{ $bautismosRegistrados === 1
                            ? 'bautismo registrado'
                            : 'bautismos registrados' }}
                    </span>

                </div>

            </div>

            <a
                href="{{ route('bautismos.index') }}"
                class="panel-destacado-boton panel-destacado-boton-azul"
            >
                <i class="bi bi-arrow-right-circle"></i>
                Abrir registro
            </a>

        </div>

    </section>

@endif

<!-- ========================================================= -->
<!-- ESTADÍSTICAS -->
<!-- ========================================================= -->

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

<!-- ========================================================= -->
<!-- MÓDULOS DE ADMINISTRACIÓN -->
<!-- ========================================================= -->

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

            <h3>
                Sin módulos disponibles
            </h3>

            <p>
                Actualmente no tienes módulos de administración
                asignados a tu rol.
            </p>

        </div>

    @endforelse

</div>

@endsection
