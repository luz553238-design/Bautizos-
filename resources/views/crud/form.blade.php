
@extends('crud.layout')

@section('title', ($row ? 'Editar · ' : 'Crear · ') . $meta['title'])

@section('content')

@php
    // ==========================================
    // CONFIGURACIÓN GENERAL
    // ==========================================

    $editando = $row !== null;

    $usuarioFormulario = auth()->user();

    $rolesFormulario = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
        ->where('ur.id_usuario', $usuarioFormulario->getAuthIdentifier())
        ->pluck('r.nombre')
        ->all();

    $esPersonal = count(array_intersect(
        $rolesFormulario,
        [
            'superadministrador',
            'administrador',
            'secretaria',
            'sacerdote'
        ]
    )) > 0;

    $esParticular = !$esPersonal;

    $tituloFormulario = $editando
        ? 'Editar registro'
        : 'Crear nuevo registro';

    $descripcionFormulario = $editando
        ? 'Modifica la información del registro seleccionado.'
        : 'Completa los campos para registrar información.';

    $accionFormulario = $editando
        ? route('crud.update', [$module, $record])
        : route('crud.store', $module);

    // ==========================================
    // CAMPOS ADMINISTRADOS POR EL SERVIDOR
    // ==========================================

    $camposProtegidos = [];

    if ($esParticular) {

        if ($module === 'solicitudes_bautismo') {
            $camposProtegidos = [
                'creado_por'
            ];

            if ($editando) {
                $camposProtegidos = array_merge(
                    $camposProtegidos,
                    [
                        'id_bautizado',
                        'id_estado_solicitud'
                    ]
                );
            }
        }

        if ($module === 'documentos') {
            $camposProtegidos = [
                'estado_revision',
                'revisado_por',
                'fecha_revision',
                'observaciones_revision'
            ];
        }

        if ($module === 'citas') {
            $camposProtegidos = ['estado'];
        }

        if (
            $editando &&
            in_array($module, [
                'solicitud_tutores',
                'solicitud_padrinos',
                'documentos',
                'citas'
            ], true)
        ) {
            $camposProtegidos[] = 'id_solicitud';
        }

        if (in_array($module, [
            'direcciones',
            'padres_tutores',
            'padrinos',
            'bautizados'
        ], true)) {
            $camposProtegidos[] = 'id_persona';
        }
    }

    $camposProtegidos = array_unique($camposProtegidos);

    $columnasFormulario = collect($meta['columns'])
        ->filter(function ($definition, $field) use ($camposProtegidos) {
            return empty($definition['auto'])
                && empty($definition['generated'])
                && !in_array($field, $camposProtegidos, true);
        })
        ->all();

    // ==========================================
    // PERMISOS PARA CREAR REGISTROS RELACIONADOS
    // ==========================================

    $esSuperadmin = in_array(
        'superadministrador',
        $rolesFormulario,
        true
    );

    $modulosProtegidos = [
        'usuarios',
        'roles',
        'usuario_rol',
        'permisos',
        'rol_permiso'
    ];

    $permisosCreacion = \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
        ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
        ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
        ->where('ur.id_usuario', $usuarioFormulario->getAuthIdentifier())
        ->whereIn('p.accion', ['ver', 'crear'])
        ->select('p.modulo', 'p.accion')
        ->distinct()
        ->get();

    $puedeCrearRelacionado = function ($tabla) use (
        $esSuperadmin,
        $modulosProtegidos,
        $permisosCreacion,
        $modules
    ) {
        if (!$tabla || !isset($modules[$tabla])) {
            return false;
        }

        if ($esSuperadmin) {
            return true;
        }

        if (in_array($tabla, $modulosProtegidos, true)) {
            return false;
        }

        return $permisosCreacion->contains(
            fn ($p) => $p->modulo === $tabla && $p->accion === 'ver'
        ) && $permisosCreacion->contains(
            fn ($p) => $p->modulo === $tabla && $p->accion === 'crear'
        );
    };
@endphp

<style>
    .crud-form-page {
        width: 100%;
        max-width: 1150px;
        margin: 0 auto;
    }

    .crud-form-back {
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

    .crud-form-back:hover {
        color: #694b76;
    }

    .crud-form-heading {
        margin-bottom: 25px;
    }

    .crud-form-heading h1 {
        font-family: 'Playfair Display', Georgia, serif;
        color: #34263f;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .crud-form-heading p {
        color: #81788a;
        font-size: 13px;
        margin-bottom: 0;
    }

    .crud-form-card {
        background: #fff;
        border: 1px solid #e9e5ee;
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(42,30,53,.04);
    }

    .crud-form-card-header {
        padding: 24px 28px;
        background: linear-gradient(110deg, #faf6fc, #ffffff);
        border-bottom: 1px solid #eeeaf2;
    }

    .crud-form-card-title {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .crud-form-card-icon {
        width: 43px;
        height: 43px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #eee4f2;
        color: #694b76;
        font-size: 19px;
    }

    .crud-form-card-title h2 {
        color: #34263f;
        font-size: 16px;
        font-weight: 800;
        margin: 0 0 5px;
    }

    .crud-form-card-title p {
        color: #81788a;
        font-size: 12px;
        margin: 0;
    }

    .crud-form-body {
        padding: 28px;
    }

    .crud-form-label {
        display: block;
        color: #51475a;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .crud-required {
        color: #cf5266;
        font-weight: 800;
        margin-left: 3px;
    }

    .crud-form-body .form-control,
    .crud-form-body .form-select {
        min-height: 44px;
        border: 1px solid #e2dce8;
        border-radius: 10px;
        color: #403747;
        font-size: 13px;
        background-color: #fff;
        padding: 10px 13px;
        transition: .2s;
    }

    .crud-form-body .form-control:focus,
    .crud-form-body .form-select:focus {
        border-color: #9875a8;
        box-shadow: 0 0 0 3px rgba(152,117,168,.12);
    }

    .crud-form-body textarea.form-control {
        min-height: 115px;
        resize: vertical;
    }

    .crud-form-hint {
        display: block;
        margin-top: 7px;
        color: #8c8294;
        font-size: 11px;
        line-height: 1.6;
    }

    .crud-form-error {
        color: #c33f52;
        font-size: 11px;
        font-weight: 600;
        margin-top: 7px;
    }

    .crud-form-footer {
        padding: 20px 28px;
        background: #fdfbfd;
        border-top: 1px solid #eeeaf2;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 11px;
    }

    .crud-form-footer .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 43px;
        padding: 10px 19px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 800;
    }

    .crud-form-note {
        padding: 12px 15px;
        border-radius: 10px;
        background: #f7f3fa;
        color: #766b80;
        font-size: 12px;
        margin-top: 22px;
    }

    .crud-form-alert {
        padding: 16px 19px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #f1d3d8;
        background: #fff5f6;
        color: #9d3347;
        font-size: 12px;
    }

    .crud-form-alert ul {
        margin: 9px 0 0;
        padding-left: 20px;
    }

    .crud-form-body .is-invalid {
        border-color: #dc6374;
    }

    .crud-form-empty {
        padding: 22px;
        border: 1px dashed #ded4e5;
        border-radius: 12px;
        background: #faf7fc;
        color: #766b80;
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .crud-form-heading h1 {
            font-size: 24px;
        }

        .crud-form-card-header,
        .crud-form-body {
            padding: 19px;
        }

        .crud-form-footer {
            padding: 18px;
            justify-content: stretch;
        }

        .crud-form-footer .btn {
            flex: 1;
        }
    }
</style>

<div class="crud-form-page">

    <!-- VOLVER AL LISTADO -->

    <a
        href="{{ route('crud.index', $module) }}"
        class="crud-form-back"
    >
        <i class="bi bi-arrow-left"></i>
        Volver a {{ strtolower($meta['title']) }}
    </a>

    <!-- ENCABEZADO -->

    <div class="crud-form-heading">

        <h1>{{ $tituloFormulario }}</h1>

        <p>{{ $descripcionFormulario }}</p>

    </div>

    <!-- ERRORES GENERALES -->

    @if($errors->any())

        <div class="crud-form-alert" role="alert">

            <strong>
                <i class="bi bi-exclamation-triangle me-1"></i>
                Revisa la información del formulario.
            </strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif

    <!-- FORMULARIO PRINCIPAL -->

    <form
        method="POST"
        action="{{ $accionFormulario }}"
        class="crud-form-card"
    >

        @csrf

        @if($editando)
            @method('PUT')
        @endif

        <!-- ENCABEZADO DE LA TARJETA -->

        <div class="crud-form-card-header">

            <div class="crud-form-card-title">

                <div class="crud-form-card-icon">
                    <i class="bi {{ $editando ? 'bi-pencil-square' : 'bi-file-earmark-plus' }}"></i>
                </div>

                <div>

                    <h2>{{ $meta['title'] }}</h2>

                    <p>
                        Los campos marcados con
                        <span class="crud-required">*</span>
                        son obligatorios.
                    </p>

                </div>

            </div>

        </div>

        <!-- CAMPOS -->

        <div class="crud-form-body">

            @if(empty($columnasFormulario))

                <div class="crud-form-empty">

                    <i class="bi bi-info-circle me-1"></i>

                    No hay campos editables disponibles para
                    este registro.

                </div>

            @else

                <div class="row g-4">

                    @foreach($columnasFormulario as $field => $definition)

                        @php
                            $tipoDato = $definition['type'] ?? 'varchar';

                            $esContrasena = $field === 'contrasena';

                            $obligatorio = !empty($definition['required'])
                                && !($editando && $esContrasena);

                            $valorGuardado = $editando
                                ? ($row->{$field} ?? null)
                                : ($definition['default'] ?? null);

                            $valor = old($field, $valorGuardado);

                            $valor = $esContrasena ? '' : $valor;

                            $esTextoLargo = $tipoDato === 'text';

                            $claseColumna = $esTextoLargo
                                ? 'col-12'
                                : 'col-md-6';

                            $tieneError = $errors->has($field);

                            $claseError = $tieneError ? 'is-invalid' : '';

                            $nombreCampo = \App\Http\Controllers\CrudController::label($field);

                            $opcionesCampo = $options[$field] ?? null;

                            $esBooleano = $tipoDato === 'boolean';

                            $valoresEnum = $definition['enum'] ?? [];

                            $esEnum = !empty($valoresEnum);

                            $esFechaHora = $tipoDato === 'datetime';

                            $esFecha = $tipoDato === 'date';

                            $esHora = $tipoDato === 'time';

                            $esNumero = in_array(
                                $tipoDato,
                                ['int', 'bigint'],
                                true
                            );

                            $tipoInput = match (true) {
                                $esContrasena => 'password',
                                $field === 'correo' => 'email',
                                $esNumero => 'number',
                                $esFechaHora => 'datetime-local',
                                $esFecha => 'date',
                                $esHora => 'time',
                                default => 'text'
                            };

                            $valorInput = $valor;

                            if ($esHora && $valorInput) {
                                $valorInput = substr(
                                    (string) $valorInput,
                                    0,
                                    5
                                );
                            }

                            if ($esFechaHora && $valorInput) {
                                $valorInput = str_replace(
                                    ' ',
                                    'T',
                                    substr((string) $valorInput, 0, 16)
                                );
                            }

                            if ($esFecha && $valorInput) {
                                $valorInput = substr(
                                    (string) $valorInput,
                                    0,
                                    10
                                );
                            }
                        @endphp

                        <div class="{{ $claseColumna }}">

                            <label
                                for="{{ $field }}"
                                class="crud-form-label"
                            >

                                {{ $nombreCampo }}

                                @if($obligatorio)
                                    <span class="crud-required">*</span>
                                @endif

                            </label>

                            <!-- CLAVES FORÁNEAS -->

                            @if(is_array($opcionesCampo))

                                <select
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    class="form-select {{ $claseError }}"
                                    @required($obligatorio)
                                >

                                    <option value="">
                                        Selecciona una opción
                                    </option>

                                    @foreach($opcionesCampo as $id => $etiqueta)

                                        <option
                                            value="{{ $id }}"
                                            @selected(
                                                $valor !== null &&
                                                (string) $valor === (string) $id
                                            )
                                        >
                                            {{ $etiqueta }}
                                        </option>

                                    @endforeach

                                </select>

                                @if(empty($opcionesCampo))

                                    @php
                                        $tablaRelacionada =
                                            $meta['foreign'][$field]['table'] ?? null;

                                        $tituloRelacionado = $tablaRelacionada
                                            ? ($modules[$tablaRelacionada]['title'] ?? $tablaRelacionada)
                                            : null;
                                    @endphp

                                    <small class="crud-form-hint">

                                        No hay registros disponibles
                                        para seleccionar.

                                        @if($puedeCrearRelacionado($tablaRelacionada))

                                            <a
                                                href="{{ route('crud.create', $tablaRelacionada) }}"
                                            >
                                                Registrar en {{ $tituloRelacionado }}
                                            </a>

                                        @endif

                                    </small>

                                @endif

                            <!-- ENUM Y BOOLEANOS -->

                            @elseif($esEnum || $esBooleano)

                                @php
                                    $opcionesSeleccion = $esBooleano
                                        ? [1 => 'Sí', 0 => 'No']
                                        : array_combine(
                                            $valoresEnum,
                                            $valoresEnum
                                        );
                                @endphp

                                <select
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    class="form-select {{ $claseError }}"
                                    @required($obligatorio)
                                >

                                    @if(!$obligatorio)
                                        <option value="">
                                            Sin especificar
                                        </option>
                                    @endif

                                    @foreach($opcionesSeleccion as $id => $etiqueta)

                                        <option
                                            value="{{ $id }}"
                                            @selected(
                                                $valor !== null &&
                                                (string) $valor === (string) $id
                                            )
                                        >
                                            {{ ucfirst((string) $etiqueta) }}
                                        </option>

                                    @endforeach

                                </select>

                            <!-- TEXTO LARGO -->

                            @elseif($esTextoLargo)

                                <textarea
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    rows="4"
                                    class="form-control {{ $claseError }}"
                                    @required($obligatorio)
                                    @if(!empty($definition['length']))
                                        maxlength="{{ $definition['length'] }}"
                                    @endif
                                >{{ $valor }}</textarea>

                            <!-- INPUTS GENERALES -->

                            @else

                                <input
                                    type="{{ $tipoInput }}"
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    class="form-control {{ $claseError }}"
                                    value="{{ $valorInput }}"
                                    @required($obligatorio)

                                    @if(!empty($definition['length']) && !$esNumero)
                                        maxlength="{{ $definition['length'] }}"
                                    @endif

                                    @if($esNumero)
                                        min="{{ $field === 'capacidad' ? 1 : 0 }}"
                                        step="1"
                                    @endif

                                    @if($esContrasena)
                                        minlength="8"
                                        autocomplete="new-password"
                                    @endif
                                >

                                @if($esContrasena)

                                    <small class="crud-form-hint">

                                        La contraseña debe contener
                                        al menos 8 caracteres.

                                        @if($editando)
                                            Déjala vacía si no deseas cambiarla.
                                        @endif

                                    </small>

                                @endif

                            @endif

                            <!-- ERRORES DEL CAMPO -->

                            @error($field)

                                <div class="crud-form-error">

                                    <i class="bi bi-exclamation-circle me-1"></i>

                                    {{ $message }}

                                </div>

                            @enderror

                        </div>

                    @endforeach

                </div>

            @endif

            <div class="crud-form-note">

                <i class="bi bi-info-circle me-1"></i>

                @if($editando)

                    Verifica la información antes de guardar los cambios.

                @else

                    Los datos registrados se almacenarán
                    en la base de datos del sistema.

                @endif

            </div>

        </div>

        <!-- BOTONES -->

        <div class="crud-form-footer">

            <a
                href="{{ route('crud.index', $module) }}"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-x-circle"></i>

                Cancelar

            </a>

            <button
                type="submit"
                class="btn btn-primary"
                @disabled(empty($columnasFormulario))
            >

                <i class="bi {{ $editando ? 'bi-check-circle' : 'bi-plus-circle' }}"></i>

                {{ $editando ? 'Guardar cambios' : 'Crear registro' }}

            </button>

        </div>

    </form>

</div>

@endsection
