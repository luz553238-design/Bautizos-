
@extends('crud.layout')

@section('title', 'Detalle · ' . $meta['title'])

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | USUARIO AUTENTICADO
    |--------------------------------------------------------------------------
    */

    $usuarioActual = auth()->user();

    $idUsuario = $usuarioActual->getAuthIdentifier();

    /*
    |--------------------------------------------------------------------------
    | COMPROBAR SUPERADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    $esSuperadmin = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
        ->where('ur.id_usuario', $idUsuario)
        ->where('r.nombre', 'superadministrador')
        ->exists();

    /*
    |--------------------------------------------------------------------------
    | PERMISOS DEL USUARIO
    |--------------------------------------------------------------------------
    */

    $permisosUsuario = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
        ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
        ->where('ur.id_usuario', $idUsuario)
        ->select('p.modulo', 'p.accion')
        ->distinct()
        ->get();

    $tienePermiso = function ($modulo, $accion) use (
        $esSuperadmin,
        $permisosUsuario
    ) {
        if ($esSuperadmin) {
            return true;
        }

        $modulosProtegidos = [
            'usuarios',
            'roles',
            'usuario_rol',
            'permisos',
            'rol_permiso'
        ];

        if (in_array($modulo, $modulosProtegidos, true)) {
            return false;
        }

        return $permisosUsuario->contains(function ($permiso) use (
            $modulo,
            $accion
        ) {
            return $permiso->modulo === $modulo
                && $permiso->accion === $accion;
        });
    };

    $puedeEditar = $tienePermiso($module, 'editar');

    $puedeEliminar = $tienePermiso($module, 'eliminar');

    /*
    |--------------------------------------------------------------------------
    | COLUMNAS VISIBLES
    |--------------------------------------------------------------------------
    */

    $columnasVisibles = collect($meta['columns'])
        ->except('contrasena')
        ->all();

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    $relaciones = [];

    foreach (($meta['foreign'] ?? []) as $campo => $fk) {

        $valor = $row->{$campo} ?? null;

        if ($valor === null || $valor === '') {
            continue;
        }

        $registroRelacionado = \Illuminate\Support\Facades\DB::table(
            $fk['table']
        )
            ->where($fk['key'], $valor)
            ->first();

        if (!$registroRelacionado) {
            continue;
        }

        $tablaRelacionada = $fk['table'];

        $puedeVerRelacionado = $tienePermiso(
            $tablaRelacionada,
            'ver'
        );

        $tokenRelacionado = null;

        if (
            $puedeVerRelacionado &&
            isset($modules[$tablaRelacionada])
        ) {
            $tokenRelacionado = \App\Http\Controllers\CrudController::token(
                $registroRelacionado,
                $modules[$tablaRelacionada]
            );
        }

        $relaciones[$campo] = [
            'nombre' => \App\Http\Controllers\CrudController::display(
                $registroRelacionado
            ),
            'tabla' => $tablaRelacionada,
            'token' => $tokenRelacionado,
            'puede_ver' => $puedeVerRelacionado
        ];
    }
@endphp

<style>
    .crud-detail-page {
        width: 100%;
        max-width: 1150px;
        margin: 0 auto;
    }

    .crud-detail-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #766b80;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        margin-bottom: 22px;
        transition: .2s;
    }

    .crud-detail-back:hover {
        color: #694b76;
    }

    .crud-detail-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
    }

    .crud-detail-heading h1 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 30px;
        font-weight: 700;
        color: #34263f;
        margin-bottom: 7px;
    }

    .crud-detail-heading p {
        color: #81788a;
        font-size: 13px;
        margin: 0;
    }

    .crud-detail-card {
        background: white;
        border: 1px solid #e9e5ee;
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(42,30,53,.04);
    }

    .crud-detail-card-header {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 23px 27px;
        background: linear-gradient(110deg, #faf6fc, #fff);
        border-bottom: 1px solid #eeeaf2;
    }

    .crud-detail-card-icon {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eee4f2;
        color: #694b76;
        border-radius: 12px;
        font-size: 20px;
    }

    .crud-detail-card-header h2 {
        font-size: 16px;
        font-weight: 800;
        color: #34263f;
        margin: 0 0 5px;
    }

    .crud-detail-card-header p {
        font-size: 12px;
        color: #81788a;
        margin: 0;
    }

    .crud-detail-content {
        padding: 10px 28px 20px;
    }

    .crud-detail-field {
        display: grid;
        grid-template-columns: minmax(180px, 33%) minmax(0, 1fr);
        gap: 20px;
        padding: 18px 0;
        border-bottom: 1px solid #f0edf3;
    }

    .crud-detail-field:last-child {
        border-bottom: none;
    }

    .crud-detail-label {
        font-size: 12px;
        font-weight: 800;
        color: #71657b;
        margin: 0;
    }

    .crud-detail-value {
        font-size: 13px;
        color: #403747;
        line-height: 1.7;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        margin: 0;
    }

    .crud-detail-value a {
        color: #694b76;
        font-weight: 700;
        text-decoration: none;
    }

    .crud-detail-value a:hover {
        text-decoration: underline;
    }

    .crud-detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 11px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
    }

    .crud-detail-badge.yes {
        background: #e7f6ee;
        color: #27784d;
    }

    .crud-detail-badge.no {
        background: #f1eef4;
        color: #777184;
    }

    .crud-detail-footer {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        padding: 20px 28px;
        background: #fdfbfd;
        border-top: 1px solid #eeeaf2;
    }

    .crud-detail-danger {
        background: white;
        border: 1px solid #f1d3d8;
        border-radius: 17px;
        overflow: hidden;
        margin-top: 25px;
    }

    .crud-detail-danger-header {
        padding: 20px 25px;
        background: #fff7f8;
        border-bottom: 1px solid #f5dfe3;
    }

    .crud-detail-danger-header h2 {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 15px;
        font-weight: 800;
        color: #b43f51;
        margin: 0;
    }

    .crud-detail-danger-body {
        padding: 23px 25px;
    }

    .crud-detail-danger-body p {
        color: #756a7c;
        font-size: 12px;
        line-height: 1.8;
        margin-bottom: 18px;
    }

    .crud-detail-danger-body .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 800;
    }

    @media (max-width: 768px) {
        .crud-detail-heading h1 {
            font-size: 24px;
        }

        .crud-detail-content {
            padding: 10px 19px 18px;
        }

        .crud-detail-field {
            grid-template-columns: 1fr;
            gap: 7px;
            padding: 15px 0;
        }

        .crud-detail-card-header,
        .crud-detail-footer {
            padding: 19px;
        }
    }
</style>

<div class="crud-detail-page">

    <!-- ==========================================
         VOLVER AL LISTADO
    =========================================== -->

    <a
        href="{{ route('crud.index', [
            'module' => $module
        ]) }}"
        class="crud-detail-back"
    >
        <i class="bi bi-arrow-left"></i>

        Volver a {{ strtolower($meta['title']) }}
    </a>

    <!-- ==========================================
         ENCABEZADO
    =========================================== -->

    <div class="crud-detail-heading">

        <div>

            <h1>Detalle del registro</h1>

            <p>
                Consulta la información registrada en
                {{ strtolower($meta['title']) }}.
            </p>

        </div>

        @if($puedeEditar)

            <a
                href="{{ route('crud.edit', [
                    'module' => $module,
                    'record' => $record
                ]) }}"
                class="btn btn-primary"
            >
                <i class="bi bi-pencil-square me-1"></i>
                Editar registro
            </a>

        @endif

    </div>

    <!-- ==========================================
         INFORMACIÓN DEL REGISTRO
    =========================================== -->

    <div class="crud-detail-card">

        <div class="crud-detail-card-header">

            <div class="crud-detail-card-icon">

                <i class="bi bi-file-earmark-text"></i>

            </div>

            <div>

                <h2>{{ $meta['title'] }}</h2>

                <p>
                    Información detallada del registro seleccionado.
                </p>

            </div>

        </div>

        <div class="crud-detail-content">

            @foreach($columnasVisibles as $field => $definition)

                @php
                    $valorCampo = $row->{$field} ?? null;

                    $esBooleano = ($definition['type'] ?? '') === 'boolean';

                    $esRelacion = isset($meta['foreign'][$field]);
                @endphp

                <div class="crud-detail-field">

                    <dt class="crud-detail-label">

                        {{ \App\Http\Controllers\CrudController::label($field) }}

                    </dt>

                    <dd class="crud-detail-value">

                        <!-- BOOLEANOS -->

                        @if($esBooleano)

                            @if((bool) $valorCampo)

                                <span class="crud-detail-badge yes">

                                    <i class="bi bi-check-circle"></i>

                                    Sí

                                </span>

                            @else

                                <span class="crud-detail-badge no">

                                    <i class="bi bi-dash-circle"></i>

                                    No

                                </span>

                            @endif

                        <!-- RELACIONES -->

                        @elseif($esRelacion)

                            @if(isset($relaciones[$field]))

                                @php
                                    $relacion = $relaciones[$field];
                                @endphp

                                @if(
                                    $relacion['puede_ver'] &&
                                    $relacion['token'] !== null
                                )

                                    <a
                                        href="{{ route('crud.show', [
                                            'module' => $relacion['tabla'],
                                            'record' => $relacion['token']
                                        ]) }}"
                                    >
                                        <i class="bi bi-box-arrow-up-right me-1"></i>

                                        {{ $relacion['nombre'] }}

                                    </a>

                                @else

                                    {{ $relacion['nombre'] }}

                                @endif

                            @else

                                —

                            @endif

                        <!-- OTROS CAMPOS -->

                        @else

                            {{ $valorCampo === null || $valorCampo === ''
                                ? '—'
                                : $valorCampo }}

                        @endif

                    </dd>

                </div>

            @endforeach

        </div>

        <!-- ==========================================
             ACCIONES INFERIORES
        =========================================== -->

        <div class="crud-detail-footer">

            <a
                href="{{ route('crud.index', [
                    'module' => $module
                ]) }}"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>

                Volver
            </a>

            @if($puedeEditar)

                <a
                    href="{{ route('crud.edit', [
                        'module' => $module,
                        'record' => $record
                    ]) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil-square me-1"></i>

                    Editar registro
                </a>

            @endif

        </div>

    </div>

    <!-- ==========================================
         ZONA DE ELIMINACIÓN
    =========================================== -->

    @if($puedeEliminar)

        <section
            id="eliminar"
            class="crud-detail-danger"
        >

            <div class="crud-detail-danger-header">

                <h2>

                    <i class="bi bi-exclamation-triangle"></i>

                    Eliminar registro

                </h2>

            </div>

            <div class="crud-detail-danger-body">

                <p>

                    Esta acción eliminará permanentemente
                    el registro seleccionado.

                    Si existen relaciones configuradas con
                    eliminación en cascada, los registros
                    dependientes también podrían eliminarse.

                    Si existen restricciones de integridad
                    referencial, MySQL puede impedir la operación.

                </p>

                <form
                    method="POST"
                    action="{{ route('crud.destroy', [
                        'module' => $module,
                        'record' => $record
                    ]) }}"
                    onsubmit="return confirm('¿Confirmas que deseas eliminar permanentemente este registro? Esta acción puede eliminar registros relacionados en cascada.')"
                >

                    @csrf

                    @method('DELETE')

                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                    >

                        <i class="bi bi-trash3"></i>

                        Eliminar definitivamente

                    </button>

                </form>

            </div>

        </section>

    @endif

</div>

@endsection
