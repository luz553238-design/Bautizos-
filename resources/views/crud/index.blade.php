
@extends('crud.layout')

@section('title', $meta['title'])

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | USUARIO ACTUAL
    |--------------------------------------------------------------------------
    */

    $usuarioActual = auth()->user();

    $idUsuario = $usuarioActual->getAuthIdentifier();

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR SUPERADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    $esSuperadmin = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
        ->where('ur.id_usuario', $idUsuario)
        ->where('r.nombre', 'superadministrador')
        ->exists();

    /*
    |--------------------------------------------------------------------------
    | CONSULTAR PERMISOS DEL MÓDULO
    |--------------------------------------------------------------------------
    */

    $accionesPermitidas = [];

    if ($esSuperadmin) {

        $accionesPermitidas = [
            'ver',
            'crear',
            'editar',
            'eliminar'
        ];

    } else {

        $accionesPermitidas = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
            ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
            ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
            ->where('ur.id_usuario', $idUsuario)
            ->where('p.modulo', $module)
            ->pluck('p.accion')
            ->unique()
            ->all();

    }

    $puedeVer = in_array('ver', $accionesPermitidas, true);
    $puedeCrear = in_array('crear', $accionesPermitidas, true);
    $puedeEditar = in_array('editar', $accionesPermitidas, true);
    $puedeEliminar = in_array('eliminar', $accionesPermitidas, true);

    /*
    |--------------------------------------------------------------------------
    | COLUMNAS VISIBLES
    |--------------------------------------------------------------------------
    */

    $columnasVisibles = collect($meta['columns'])
        ->except('contrasena')
        ->all();

    $mostrarAcciones = $puedeVer || $puedeEditar || $puedeEliminar;

    $totalColumnas = count($columnasVisibles) + ($mostrarAcciones ? 1 : 0);

    /*
    |--------------------------------------------------------------------------
    | CACHE LOCAL DE RELACIONES
    |--------------------------------------------------------------------------
    */

    $relaciones = [];

    foreach (($meta['foreign'] ?? []) as $campo => $fk) {

        $ids = collect($rows->items())
            ->pluck($campo)
            ->filter(fn ($valor) => $valor !== null && $valor !== '')
            ->unique()
            ->values()
            ->all();

        $relaciones[$campo] = [];

        if (!empty($ids)) {

            $registrosRelacionados = \Illuminate\Support\Facades\DB::table($fk['table'])
                ->whereIn($fk['key'], $ids)
                ->get();

            foreach ($registrosRelacionados as $relacionado) {

                $relaciones[$campo][(string) $relacionado->{$fk['key']}] =
                    \App\Http\Controllers\CrudController::display($relacionado);

            }

        }

    }
@endphp

<style>
    .crud-page {
        width: 100%;
    }

    .crud-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 25px;
    }

    .crud-heading h1 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 29px;
        font-weight: 700;
        color: #34263f;
        margin-bottom: 7px;
    }

    .crud-description {
        color: #777184;
        font-size: 13px;
        margin: 0;
    }

    .crud-search {
        background: white;
        border: 1px solid #e9e5ee;
        border-radius: 15px;
        padding: 19px;
        margin-bottom: 22px;
        box-shadow: 0 6px 24px rgba(42,30,53,.03);
    }

    .crud-search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .crud-search-input {
        position: relative;
        flex: 1;
        min-width: 230px;
    }

    .crud-search-input i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #93869d;
        pointer-events: none;
    }

    .crud-search-input input {
        padding-left: 40px;
        min-height: 43px;
    }

    .crud-table-card {
        background: white;
        border: 1px solid #e9e5ee;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 6px 24px rgba(42,30,53,.035);
    }

    .crud-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        padding: 18px 22px;
        border-bottom: 1px solid #eeeaf2;
    }

    .crud-table-title {
        font-size: 15px;
        font-weight: 800;
        color: #34263f;
        margin: 0;
    }

    .crud-count {
        background: #f2eaf4;
        color: #694b76;
        border-radius: 20px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
    }

    .crud-table {
        width: 100%;
        margin: 0;
    }

    .crud-table thead th {
        background: #faf8fc;
        color: #65596d;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 15px 17px;
        white-space: nowrap;
        border-bottom: 1px solid #e9e5ee;
    }

    .crud-table tbody td {
        padding: 14px 17px;
        color: #514b5a;
        font-size: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f0edf3;
        max-width: 300px;
        overflow-wrap: anywhere;
    }

    .crud-table tbody tr:hover {
        background: #fcfaff;
    }

    .crud-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: nowrap;
    }

    .crud-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 11px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        white-space: nowrap;
        border: 1px solid transparent;
        cursor: pointer;
        transition: .2s;
    }

    .crud-btn-view {
        background: #eef5fc;
        border-color: #d4e5f6;
        color: #326d9b;
    }

    .crud-btn-view:hover {
        background: #dcecfb;
        color: #245579;
    }

    .crud-btn-edit {
        background: #fff6e7;
        border-color: #f1dfba;
        color: #9a6b22;
    }

    .crud-btn-edit:hover {
        background: #ffedca;
        color: #795018;
    }

    .crud-btn-delete {
        background: #fff0f1;
        border-color: #f3d4d8;
        color: #b43f51;
    }

    .crud-btn-delete:hover {
        background: #fbdde2;
        color: #932b3b;
    }

    .crud-empty {
        text-align: center;
        padding: 60px 20px !important;
    }

    .crud-empty-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 17px;
        border-radius: 20px;
        background: #f2eaf4;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #694b76;
        font-size: 30px;
    }

    .crud-pagination {
        margin-top: 22px;
    }

    .crud-pagination .pagination {
        flex-wrap: wrap;
        gap: 4px;
    }

    .crud-pagination .page-link {
        border-radius: 8px;
        color: #694b76;
    }

    .crud-pagination .active .page-link {
        background: #694b76;
        border-color: #694b76;
        color: white;
    }

    @media (max-width: 768px) {
        .crud-heading {
            align-items: stretch;
        }

        .crud-heading h1 {
            font-size: 24px;
        }

        .crud-search-form {
            flex-direction: column;
            align-items: stretch;
        }

        .crud-search-input {
            width: 100%;
            min-width: 0;
        }

        .crud-actions {
            flex-wrap: wrap;
        }

        .crud-table thead th,
        .crud-table tbody td {
            padding: 11px;
        }
    }
</style>

<div class="crud-page">

    <!-- =========================================
         ENCABEZADO
    ========================================== -->

    <div class="crud-heading">

        <div>
            <h1>{{ $meta['title'] }}</h1>

            <p class="crud-description">
                {{ $rows->total() }} registros encontrados.
                Consulta y administra la información del módulo.
            </p>
        </div>

        @if($puedeCrear)

            <a
                href="{{ route('crud.create', ['module' => $module]) }}"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-circle"></i>
                Crear registro
            </a>

        @endif

    </div>

    <!-- =========================================
         BUSCADOR
    ========================================== -->

    <div class="crud-search">

        <form
            method="GET"
            action="{{ route('crud.index', ['module' => $module]) }}"
            class="crud-search-form"
        >

            <div class="crud-search-input">

                <i class="bi bi-search"></i>

                <input
                    type="search"
                    name="q"
                    class="form-control"
                    value="{{ $search }}"
                    placeholder="Buscar registros..."
                    aria-label="Buscar registros"
                >

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-search"></i>
                Buscar
            </button>

            @if($search !== '')

                <a
                    href="{{ route('crud.index', ['module' => $module]) }}"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-x-circle"></i>
                    Limpiar
                </a>

            @endif

        </form>

    </div>

    <!-- =========================================
         TABLA
    ========================================== -->

    <div class="crud-table-card">

        <div class="crud-table-header">

            <h2 class="crud-table-title">
                <i class="bi bi-table me-2"></i>
                Listado de registros
            </h2>

            <span class="crud-count">
                {{ $rows->total() }} registros
            </span>

        </div>

        <div class="table-responsive">

            <table class="table table-hover crud-table">

                <thead>
                    <tr>

                        @foreach($columnasVisibles as $field => $definition)

                            <th scope="col">
                                {{ \App\Http\Controllers\CrudController::label($field) }}
                            </th>

                        @endforeach

                        @if($mostrarAcciones)
                            <th scope="col">
                                Acciones
                            </th>
                        @endif

                    </tr>
                </thead>

                <tbody>

                    @forelse($rows as $row)

                        @php
                            $token = \App\Http\Controllers\CrudController::token(
                                $row,
                                $meta
                            );
                        @endphp

                        <tr>

                            @foreach($columnasVisibles as $field => $definition)

                                <td>

                                    @if(($definition['type'] ?? '') === 'boolean')

                                        @if((bool) ($row->$field ?? false))

                                            <span class="badge text-bg-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Sí
                                            </span>

                                        @else

                                            <span class="badge text-bg-secondary">
                                                <i class="bi bi-dash-circle me-1"></i>
                                                No
                                            </span>

                                        @endif

                                    @elseif(isset($meta['foreign'][$field]))

                                        @php
                                            $valorRelacion = $row->$field ?? null;

                                            $textoRelacion = $valorRelacion !== null
                                                ? ($relaciones[$field][(string) $valorRelacion] ?? null)
                                                : null;
                                        @endphp

                                        {{ $textoRelacion ?? '—' }}

                                    @else

                                        {{ \Illuminate\Support\Str::limit(
                                            (string) ($row->$field ?? '—'),
                                            90
                                        ) }}

                                    @endif

                                </td>

                            @endforeach

                            @if($mostrarAcciones)

                                <td>

                                    <div class="crud-actions">

                                        <!-- VER -->
                                        @if($puedeVer)

                                            <a
                                                href="{{ route('crud.show', [
                                                    'module' => $module,
                                                    'record' => $token
                                                ]) }}"
                                                class="crud-action-btn crud-btn-view"
                                                title="Ver detalles"
                                            >
                                                <i class="bi bi-eye"></i>
                                                Ver
                                            </a>

                                        @endif

                                        <!-- EDITAR -->
                                        @if($puedeEditar)

                                            <a
                                                href="{{ route('crud.edit', [
                                                    'module' => $module,
                                                    'record' => $token
                                                ]) }}"
                                                class="crud-action-btn crud-btn-edit"
                                                title="Editar registro"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                Editar
                                            </a>

                                        @endif

                                        <!-- ELIMINAR -->
                                        @if($puedeEliminar)

                                            <form
                                                method="POST"
                                                action="{{ route('crud.destroy', [
                                                    'module' => $module,
                                                    'record' => $token
                                                ]) }}"
                                                class="m-0"
                                                onsubmit="return confirm('¿Estás seguro de que deseas eliminar este registro? Esta acción puede ser irreversible.')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="crud-action-btn crud-btn-delete"
                                                    title="Eliminar registro"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                    Eliminar
                                                </button>

                                            </form>

                                        @endif

                                    </div>

                                </td>

                            @endif

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="{{ max(1, $totalColumnas) }}"
                                class="crud-empty"
                            >

                                <div class="crud-empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>

                                <h3 class="h5 mb-2">

                                    {{ $search !== ''
                                        ? 'No se encontraron coincidencias'
                                        : 'Todavía no hay registros' }}

                                </h3>

                                <p class="text-secondary mb-3">

                                    {{ $search !== ''
                                        ? 'Prueba con otro término de búsqueda.'
                                        : 'Aún no existen registros en este módulo.' }}

                                </p>

                                @if($search !== '')

                                    <a
                                        href="{{ route('crud.index', [
                                            'module' => $module
                                        ]) }}"
                                        class="btn btn-outline-secondary"
                                    >
                                        Limpiar búsqueda
                                    </a>

                                @elseif($puedeCrear)

                                    <a
                                        href="{{ route('crud.create', [
                                            'module' => $module
                                        ]) }}"
                                        class="btn btn-primary"
                                    >
                                        <i class="bi bi-plus-circle"></i>
                                        Crear primer registro
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <!-- =========================================
         PAGINACIÓN
    ========================================== -->

    @if($rows->hasPages())

        <div class="crud-pagination">

            {{ $rows->links() }}

        </div>

    @endif

</div>

@endsection
