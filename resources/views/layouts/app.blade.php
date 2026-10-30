
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistema de Gestión de Bautizos')</title>

    <!-- BOOTSTRAP LOCAL -->
    <link
        href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}"
        rel="stylesheet"
    >

    <!-- TIPOGRAFÍAS -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- ICONOS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --primary: #694b76;
            --primary-dark: #34263f;
            --primary-light: #f2eaf4;
            --accent: #c5a77a;
            --background: #f7f6fa;
            --surface: #ffffff;
            --text: #252238;
            --muted: #777589;
            --border: #e9e5ee;
            --sidebar-width: 280px;
            --radius: 16px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
        }

        a {
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        /* =========================================
           ESTRUCTURA GENERAL
        ========================================= */

        .app-shell {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(
                180deg,
                #34263f 0%,
                #292034 100%
            );
            color: white;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 8px 0 35px rgba(31, 22, 42, .08);
        }

        /* =========================================
           ENCABEZADO DEL MENÚ
        ========================================= */

        .sidebar-header {
            padding: 31px 24px 25px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            background: rgba(197,167,122,.16);
            color: #e6cda6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 17px;
        }

        .brand-label {
            color: #c8b6d0;
            font-size: 10px;
            letter-spacing: 2px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 31px;
            font-weight: 600;
            margin: 8px 0;
        }

        .brand-description {
            color: #b8afc2;
            font-size: 12px;
            line-height: 1.8;
        }

        /* =========================================
           NAVEGACIÓN LATERAL
        ========================================= */

        .sidebar-navigation {
            flex: 1;
            overflow-y: auto;
            padding: 22px 13px;
            scrollbar-width: thin;
            scrollbar-color: #65546f transparent;
        }

        .navigation-label {
            padding: 15px 13px 9px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.7px;
            color: #9e8ca9;
            text-transform: uppercase;
        }

        .navigation-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 14px;
            margin: 3px 0;
            border-radius: 11px;
            color: #d6cddd;
            font-size: 13px;
            font-weight: 500;
            transition: .2s ease;
        }

        .navigation-link i {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        .navigation-link:hover {
            background: rgba(255,255,255,.08);
            color: white;
        }

        .navigation-link.active {
            background: rgba(197,167,122,.18);
            color: #f2ddbc;
            font-weight: 700;
        }

        /* =========================================
           PERFIL DEL USUARIO
        ========================================= */

        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.08);
            padding: 18px;
        }

        .sidebar-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: #82658e;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 17px;
            flex-shrink: 0;
        }

        .sidebar-user {
            font-weight: 700;
            color: white;
            font-size: 13px;
        }

        .sidebar-role {
            color: #b7aabe;
            font-size: 11px;
            margin-top: 4px;
        }

        /* =========================================
           CONTENIDO PRINCIPAL
        ========================================= */

        .main {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            min-width: 0;
        }

        .topbar {
            min-height: 83px;
            background: rgba(255,255,255,.94);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 35px;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(14px);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-title {
            font-size: 17px;
            font-weight: 800;
        }

        .topbar-subtitle {
            color: var(--muted);
            font-size: 11px;
            margin-top: 5px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 17px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 12px;
        }

        .topbar-user i {
            color: var(--primary);
            font-size: 22px;
        }

        .logout-button {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: white;
            padding: 10px 14px;
            color: var(--text);
            cursor: pointer;
            transition: .2s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .logout-button:hover {
            background: #fff0f0;
            border-color: #e7b6b6;
            color: #a12b2b;
        }

        .content {
            max-width: 1700px;
            margin: 0 auto;
            padding: 32px 36px 60px;
            min-width: 0;
        }

        /* =========================================
           TÍTULOS
        ========================================= */

        .page-intro {
            margin-bottom: 26px;
        }

        .page-eyebrow {
            color: var(--primary);
            font-size: 10px;
            letter-spacing: 1.8px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .page-heading {
            font-family: 'Playfair Display', serif;
            font-size: 31px;
            font-weight: 600;
            margin: 0;
        }

        .page-description {
            color: var(--muted);
            margin-top: 8px;
        }

        /* =========================================
           TARJETAS
        ========================================= */

        .content-card,
        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 8px 28px rgba(42,30,53,.035);
        }

        /* =========================================
           BOTONES
        ========================================= */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border-radius: 9px;
            font-weight: 600;
            padding: 9px 15px;
            text-decoration: none;
            transition: all .2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: white;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        .btn-secondary {
            background: #777184;
            border: 1px solid #777184;
            color: white;
        }

        .btn-secondary:hover {
            background: #625b6e;
            border-color: #625b6e;
            color: white;
        }

        .btn-outline-secondary {
            border: 1px solid #ded5e5;
            color: #51435b;
            background: white;
        }

        .btn-outline-secondary:hover {
            background: var(--primary-light);
            border-color: #c9b9d1;
            color: var(--primary-dark);
        }

        .btn-success {
            background: #25805e;
            border: 1px solid #25805e;
            color: white;
        }

        .btn-success:hover {
            background: #1c6449;
            border-color: #1c6449;
            color: white;
        }

        .btn-danger {
            background: #c64b5c;
            border: 1px solid #c64b5c;
            color: white;
        }

        .btn-danger:hover {
            background: #a53646;
            border-color: #a53646;
            color: white;
        }

        .btn-warning {
            background: #d9a347;
            border: 1px solid #d9a347;
            color: #2d2431;
        }

        .btn-warning:hover {
            background: #c38c2d;
            border-color: #c38c2d;
            color: #2d2431;
        }

        .btn-info {
            background: #4385a6;
            border: 1px solid #4385a6;
            color: white;
        }

        .btn-info:hover {
            background: #326d8b;
            border-color: #326d8b;
            color: white;
        }

        .btn-sm {
            padding: 6px 11px;
            font-size: 12px;
            border-radius: 7px;
        }

        /* =========================================
           TABLAS
        ========================================= */

        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 0;
        }

        .table th,
        .table td {
            padding: 13px 15px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }

        .table th {
            background: #faf8fc;
            color: #625b6c;
            font-weight: 700;
            font-size: 12px;
        }

        .table tbody tr:hover {
            background: #fcfaff;
        }

        .table td .btn {
            margin: 2px;
        }

        /* =========================================
           FORMULARIOS
        ========================================= */

        .form-control,
        .form-select {
            width: 100%;
            border: 1px solid #ddd7e5;
            border-radius: 9px;
            padding: 10px 12px;
            background: white;
            color: var(--text);
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(105,75,118,.12);
        }

        .form-label {
            font-weight: 700;
            color: #51435b;
            margin-bottom: 7px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* =========================================
           ALERTAS
        ========================================= */

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #eaf7ee;
            color: #246c3d;
            border: 1px solid #cdebd5;
        }

        .alert-danger {
            background: #fff0f0;
            color: #a33131;
            border: 1px solid #f0cece;
        }

        .alert-warning {
            background: #fff7e8;
            color: #805a1e;
            border: 1px solid #f1ddb6;
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        .mobile-toggle {
            display: none;
            background: none;
            border: 0;
            color: var(--primary);
            font-size: 23px;
            cursor: pointer;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 1050px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.open {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(20,14,30,.5);
                z-index: 999;
            }

            .main {
                width: 100%;
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }
        }

        @media (max-width: 600px) {
            .topbar {
                padding: 15px;
            }

            .content {
                padding: 22px 15px;
            }

            .topbar-user span {
                display: none;
            }

            .topbar-title {
                font-size: 15px;
            }

            .page-heading {
                font-size: 25px;
            }

            .logout-button {
                padding: 9px;
                font-size: 0;
            }

            .logout-button i {
                font-size: 18px;
            }

            .table th,
            .table td {
                padding: 10px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    @php

        /*
        |--------------------------------------------------------------------------
        | USUARIO AUTENTICADO
        |--------------------------------------------------------------------------
        */

        $usuarioActual = auth()->user();

        $nombreUsuario = $usuarioActual->nombre_usuario ?? 'Usuario';

        $rolesActuales = [];

        $esSuperadmin = false;
        $esAdministrador = false;
        $esCliente = false;

        if ($usuarioActual) {

            $rolesActuales = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
                ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
                ->where(
                    'ur.id_usuario',
                    $usuarioActual->getAuthIdentifier()
                )
                ->pluck('r.nombre')
                ->all();

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

            $esCliente = in_array(
                'cliente',
                $rolesActuales,
                true
            );
        }

        $esPersonalAdministrativo = count(
            array_intersect(
                $rolesActuales,
                [
                    'superadministrador',
                    'administrador',
                    'secretaria',
                    'sacerdote'
                ]
            )
        ) > 0;

        $esClienteSinAdministracion =
            $esCliente && !$esPersonalAdministrativo;

        $puedeAdministrarIglesias =
            $esSuperadmin || $esAdministrador;

        $rolActual = $esSuperadmin
            ? 'Superadministrador'
            : ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $rolesActuales[0] ?? 'Usuario'
                )
            );

        $inicial = mb_strtoupper(
            mb_substr($nombreUsuario, 0, 1)
        );

        /*
        |--------------------------------------------------------------------------
        | MÓDULOS CRUD VISIBLES
        |--------------------------------------------------------------------------
        |
        | Direcciones se sustituye por el módulo independiente
        | Iglesias y direcciones.
        |
        | Se mantienen ocultos:
        | sacerdotes, secretarias, estados_solicitud,
        | solicitud_padrinos, tipos_documento, documentos,
        | citas, historial_citas, bautismos, roles,
        | usuario_rol y direcciones.
        |
        | Las tablas siguen existiendo en la base de datos.
        |--------------------------------------------------------------------------
        */

        $modulos = [

            'personas' => [
                'Personas',
                'bi-people'
            ],

            'usuarios' => [
                'Usuarios',
                'bi-person-badge'
            ],

            'padres_tutores' => [
                'Padres y tutores',
                'bi-people-fill'
            ],

            'padrinos' => [
                'Padrinos',
                'bi-person-heart'
            ],

            'bautizados' => [
                'Bautizados',
                'bi-person-check'
            ],

            'solicitudes_bautismo' => [
                'Solicitudes de bautismo',
                'bi-file-earmark-text'
            ],

            'solicitud_tutores' => [
                'Solicitud de tutores',
                'bi-person-lines-fill'
            ],

            'horarios_bautizo' => [
                'Horarios',
                'bi-clock-history'
            ]

        ];

        /*
        |--------------------------------------------------------------------------
        | PERMISOS DE LOS MÓDULOS CRUD
        |--------------------------------------------------------------------------
        */

        $modulosVisibles = [];

        if ($usuarioActual && $esSuperadmin) {

            $modulosVisibles = array_keys($modulos);

        } elseif ($usuarioActual && !$esClienteSinAdministracion) {

            $modulosVisibles = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
                ->join(
                    'rol_permiso as rp',
                    'ur.id_rol',
                    '=',
                    'rp.id_rol'
                )
                ->join(
                    'permisos as p',
                    'rp.id_permiso',
                    '=',
                    'p.id_permiso'
                )
                ->where(
                    'ur.id_usuario',
                    $usuarioActual->getAuthIdentifier()
                )
                ->where('p.accion', 'ver')
                ->distinct()
                ->pluck('p.modulo')
                ->all();

            $modulosVisibles = array_values(
                array_intersect(
                    array_keys($modulos),
                    $modulosVisibles
                )
            );

            // Usuarios es exclusivo del superadministrador.
            $modulosVisibles = array_values(
                array_diff(
                    $modulosVisibles,
                    [
                        'usuarios',
                        'roles',
                        'usuario_rol'
                    ]
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TÍTULO DE LA PÁGINA
        |--------------------------------------------------------------------------
        */

        $moduloActual = request()->route('module');

        $tituloPagina = 'Panel de administración';

        if (
            $moduloActual &&
            isset($modulos[$moduloActual])
        ) {

            $tituloPagina = $modulos[$moduloActual][0];

        } elseif (request()->routeIs('iglesias.*')) {

            $tituloPagina = 'Iglesias y direcciones';

        } elseif (request()->routeIs('cliente.*')) {

            $tituloPagina = 'Mi espacio';

        } elseif (request()->routeIs('solicitudes.*')) {

            $tituloPagina = 'Solicitudes de bautismo';

        } elseif (request()->routeIs('revision.*')) {

            $tituloPagina = 'Revisión de solicitudes';

        } elseif (request()->routeIs('bautismos.historial')) {

            $tituloPagina = 'Historial de bautismos';

        } elseif (request()->routeIs('bautismos.*')) {

            $tituloPagina = 'Registro de bautismos';

        } elseif (request()->routeIs('admin.usuarios*')) {

            $tituloPagina = 'Administración de usuarios';

        } elseif (request()->routeIs('admin.permisos*')) {

            $tituloPagina = 'Administración de permisos';

        }

    @endphp

    <div class="app-shell">

        <!-- CAPA PARA EL MENÚ MÓVIL -->

        <div
            class="sidebar-overlay"
            id="sidebarOverlay"
        ></div>

        <!-- =====================================
             MENÚ LATERAL
        ====================================== -->

        <aside class="sidebar" id="sidebar">

            <div class="sidebar-header">

                <div class="brand-icon">
                    <i class="bi bi-buildings"></i>
                </div>

                <div class="brand-label">
                    Administración parroquial
                </div>

                <div class="brand-title">
                    Bautizos
                </div>

                <div class="brand-description">
                    Plataforma de gestión de personas,
                    solicitudes y celebraciones.
                </div>

            </div>

            <nav class="sidebar-navigation">

                <div class="navigation-label">
                    Navegación principal
                </div>

                @if($esClienteSinAdministracion)

                    @if(Route::has('cliente.dashboard'))

                        <a
                            href="{{ route('cliente.dashboard') }}"
                            class="navigation-link {{ request()->routeIs('cliente.*') ? 'active' : '' }}"
                        >
                            <i class="bi bi-house-door"></i>
                            <span>Mi panel</span>
                        </a>

                    @endif

                    @if(Route::has('solicitudes.menu'))

                        <a
                            href="{{ route('solicitudes.menu') }}"
                            class="navigation-link {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}"
                        >
                            <i class="bi bi-file-earmark-text"></i>
                            <span>Mis solicitudes</span>
                        </a>

                    @endif

                @else

                    <!-- PANEL PRINCIPAL -->

                    @if(Route::has('dashboard'))

                        <a
                            href="{{ route('dashboard') }}"
                            class="navigation-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        >
                            <i class="bi bi-grid-1x2"></i>
                            <span>Menú principal</span>
                        </a>

                    @endif

                    <!-- MÓDULOS CRUD -->

                    @if(
                        count($modulosVisibles) > 0 ||
                        $puedeAdministrarIglesias
                    )

                        <div class="navigation-label">
                            Gestión parroquial
                        </div>

                        @foreach($modulosVisibles as $clave)

                            @if(isset($modulos[$clave]))

                                <a
                                    href="{{ route('crud.index', ['module' => $clave]) }}"
                                    class="navigation-link {{ request()->routeIs('crud.*') && $moduloActual === $clave ? 'active' : '' }}"
                                >
                                    <i class="bi {{ $modulos[$clave][1] }}"></i>

                                    <span>
                                        {{ $modulos[$clave][0] }}
                                    </span>
                                </a>

                            @endif

                        @endforeach

                        <!-- NUEVO MÓDULO IGLESIAS Y DIRECCIONES -->

                        @if(
                            $puedeAdministrarIglesias &&
                            Route::has('iglesias.index')
                        )

                            <a
                                href="{{ route('iglesias.index') }}"
                                class="navigation-link {{ request()->routeIs('iglesias.*') ? 'active' : '' }}"
                            >
                                <i class="bi bi-buildings"></i>

                                <span>
                                    Iglesias y direcciones
                                </span>
                            </a>

                        @endif

                    @endif

                    <!-- SEGURIDAD Y PERMISOS -->

                    @if($esSuperadmin)

                        <div class="navigation-label">
                            Seguridad y permisos
                        </div>

                        @if(Route::has('admin.usuarios'))

                            <a
                                href="{{ route('admin.usuarios') }}"
                                class="navigation-link {{ request()->routeIs('admin.usuarios*') ? 'active' : '' }}"
                            >
                                <i class="bi bi-person-gear"></i>
                                <span>Asignar roles</span>
                            </a>

                        @endif

                        @if(Route::has('admin.permisos'))

                            <a
                                href="{{ route('admin.permisos') }}"
                                class="navigation-link {{ request()->routeIs('admin.permisos*') ? 'active' : '' }}"
                            >
                                <i class="bi bi-shield-check"></i>
                                <span>Administrar permisos</span>
                            </a>

                        @endif

                    @endif

                @endif

            </nav>

            <!-- =====================================
                 PERFIL DEL USUARIO
            ====================================== -->

            <div class="sidebar-footer">

                <div class="sidebar-profile">

                    <div class="sidebar-avatar">
                        {{ $inicial }}
                    </div>

                    <div>

                        <div class="sidebar-user">
                            {{ $nombreUsuario }}
                        </div>

                        <div class="sidebar-role">
                            {{ $rolActual }}
                        </div>

                    </div>

                </div>

            </div>

        </aside>

        <!-- =====================================
             CONTENIDO PRINCIPAL
        ====================================== -->

        <main class="main">

            <!-- ENCABEZADO SUPERIOR -->

            <header class="topbar">

                <div class="topbar-left">

                    <button
                        type="button"
                        class="mobile-toggle"
                        id="sidebarToggle"
                        aria-label="Abrir menú"
                        aria-controls="sidebar"
                        aria-expanded="false"
                    >
                        <i class="bi bi-list"></i>
                    </button>

                    <div>

                        <div class="topbar-title">
                            {{ $tituloPagina }}
                        </div>

                        <div class="topbar-subtitle">
                            Sistema de Gestión de Bautizos
                        </div>

                    </div>

                </div>

                <div class="topbar-right">

                    <div class="topbar-user">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ $nombreUsuario }}</span>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                        class="m-0"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="logout-button"
                        >
                            <i class="bi bi-box-arrow-right"></i>
                            Cerrar sesión
                        </button>

                    </form>

                </div>

            </header>

            <!-- ÁREA DE TRABAJO -->

            <div class="content">

                @if(session('success'))

                    <div
                        class="alert alert-success"
                        role="status"
                    >
                        <i class="bi bi-check-circle"></i>
                        {{ session('success') }}
                    </div>

                @endif

                @if(session('error'))

                    <div
                        class="alert alert-danger"
                        role="alert"
                    >
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ session('error') }}
                    </div>

                @endif

                @if($errors->any())

                    <div
                        class="alert alert-danger"
                        role="alert"
                    >

                        <strong>
                            Revisa los siguientes errores:
                        </strong>

                        <ul class="mb-0 mt-2">

                            @foreach($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

                @yield('content')

            </div>

        </main>

    </div>

    <!-- =====================================
         FUNCIONES DEL MENÚ MÓVIL
    ====================================== -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            function cerrarMenu() {

                if (sidebar) {
                    sidebar.classList.remove('open');
                }

                if (overlay) {
                    overlay.classList.remove('open');
                }

                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }

            if (toggle && sidebar && overlay) {

                toggle.addEventListener('click', function () {

                    const abierto = sidebar.classList.toggle('open');

                    overlay.classList.toggle('open', abierto);

                    toggle.setAttribute(
                        'aria-expanded',
                        abierto ? 'true' : 'false'
                    );

                });
            }

            if (overlay) {
                overlay.addEventListener('click', cerrarMenu);
            }

            document.addEventListener('keydown', function (event) {

                if (event.key === 'Escape') {
                    cerrarMenu();
                }

            });

            document.querySelectorAll('.sidebar .navigation-link')
                .forEach(function (enlace) {

                    enlace.addEventListener('click', cerrarMenu);

                });

        });
    </script>

    @stack('scripts')

</body>
</html>
