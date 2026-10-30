
@extends('layouts.app')

@section('content')

<style>
    .bautismos-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    /* ENCABEZADO */

    .bautismos-encabezado {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 25px;
    }

    .bautismos-encabezado-texto {
        flex: 1;
        min-width: 250px;
    }

    .bautismos-titulo {
        color: #704092;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .bautismos-descripcion {
        color: #6b6475;
        margin-bottom: 0;
        line-height: 1.6;
    }

    /* TARJETA */

    .bautismos-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        border: 1px solid #e8dfeb;
    }

    /* BOTONES */

    .bautismos-boton {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #79527e;
        color: white;
        padding: 11px 16px;
        border-radius: 8px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        transition: background 0.2s ease;
    }

    .bautismos-boton:hover {
        background: #603e66;
        color: white;
        text-decoration: none;
    }

    .bautismos-historial {
        background: #704092;
        white-space: nowrap;
    }

    .bautismos-historial:hover {
        background: #583071;
    }

    .bautismos-descargar {
        background: #28734b;
    }

    .bautismos-descargar:hover {
        background: #1c5738;
    }

    /* BUSCADOR */

    .bautismos-busqueda {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .bautismos-busqueda input {
        flex: 1;
        min-width: 220px;
        padding: 11px;
        border: 1px solid #ddd;
        border-radius: 8px;
        outline: none;
    }

    .bautismos-busqueda input:focus {
        border-color: #79527e;
        box-shadow: 0 0 0 3px rgba(121, 82, 126, 0.12);
    }

    /* MENSAJES */

    .bautismos-exito {
        background: #e4f7e9;
        color: #166534;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .bautismos-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    /* TABLA */

    .bautismos-tabla-contenedor {
        width: 100%;
        overflow-x: auto;
    }

    .bautismos-tabla {
        width: 100%;
        border-collapse: collapse;
    }

    .bautismos-tabla th,
    .bautismos-tabla td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .bautismos-tabla th {
        background: #f5edf8;
        color: #65417e;
        font-weight: 700;
        white-space: nowrap;
    }

    .bautismos-tabla td {
        color: #51465a;
        font-size: 13px;
    }

    .bautismos-tabla tbody tr:hover {
        background: #fcf9fd;
    }

    /* ESTADOS */

    .bautismos-estado {
        color: #16803c;
        font-weight: bold;
    }

    .bautismos-pendiente {
        color: #8a6700;
        font-weight: 500;
    }

    .bautismos-no-disponible {
        color: #8b8591;
        font-size: 13px;
    }

    /* ACCIONES */

    .bautismos-acciones {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .bautismos-folio {
        font-size: 12px;
        color: #51465a;
    }

    .bautismos-folio strong {
        color: #704092;
    }

    /* SIN REGISTROS */

    .bautismos-vacio {
        text-align: center !important;
        color: #777;
        padding: 30px !important;
    }

    /* PAGINACIÓN */

    .bautismos-paginacion {
        margin-top: 20px;
    }

    /* RESPONSIVE */

    @media (max-width: 768px) {

        .bautismos-container {
            padding: 0 12px;
        }

        .bautismos-card {
            padding: 15px;
        }

        .bautismos-titulo {
            font-size: 24px;
        }

        .bautismos-encabezado {
            flex-direction: column;
            align-items: flex-start;
        }

        .bautismos-historial {
            width: 100%;
        }

        .bautismos-tabla {
            min-width: 850px;
        }

        .bautismos-busqueda {
            flex-direction: column;
        }

        .bautismos-busqueda input {
            width: 100%;
        }
    }
</style>

@php

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR ROL DEL USUARIO
    |--------------------------------------------------------------------------
    | Solo superadministrador y administrador pueden
    | registrar bautismos y descargar certificados
    | desde esta pantalla.
    |--------------------------------------------------------------------------
    */

    $usuarioActual = auth()->user();

    $idUsuarioActual = $usuarioActual
        ? $usuarioActual->getAuthIdentifier()
        : null;

    $esAdministrativo = false;

    if ($idUsuarioActual !== null) {

        $esAdministrativo =
            \Illuminate\Support\Facades\DB::table('usuario_rol as ur')
                ->join(
                    'roles as r',
                    'r.id_rol',
                    '=',
                    'ur.id_rol'
                )
                ->where(
                    'ur.id_usuario',
                    $idUsuarioActual
                )
                ->whereIn(
                    \Illuminate\Support\Facades\DB::raw(
                        'LOWER(TRIM(r.nombre))'
                    ),
                    [
                        'superadministrador',
                        'administrador'
                    ]
                )
                ->exists();
    }

@endphp

<div class="bautismos-container">

    <!-- ENCABEZADO -->

    <div class="bautismos-encabezado">

        <div class="bautismos-encabezado-texto">

            <h1 class="bautismos-titulo">
                Registro de bautismos
            </h1>

            <p class="bautismos-descripcion">
                Consulta las solicitudes aprobadas, registra las
                ceremonias realizadas y descarga los certificados
                de bautismo correspondientes.
            </p>

        </div>

        <!-- NUEVO BOTÓN DEL HISTORIAL -->

        @if($esAdministrativo)

            <a
                href="{{ route('bautismos.historial') }}"
                class="bautismos-boton bautismos-historial"
                title="Consultar historial de bautismos"
            >
                <i class="bi bi-clock-history"></i>
                Ver historial de bautismos
            </a>

        @endif

    </div>

    <!-- MENSAJES -->

    @if(session('success'))

        <div class="bautismos-exito">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div class="bautismos-error">
            {{ session('error') }}
        </div>

    @endif

    @if($errors->any())

        <div class="bautismos-error">

            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach

        </div>

    @endif

    <!-- TARJETA PRINCIPAL -->

    <div class="bautismos-card">

        <!-- BUSCADOR -->

        <form
            method="GET"
            action="{{ route('bautismos.index') }}"
            class="bautismos-busqueda"
        >

            <input
                type="text"
                name="buscar"
                value="{{ $buscar ?? '' }}"
                placeholder="Buscar por folio de solicitud"
            >

            <button
                type="submit"
                class="bautismos-boton"
            >
                <i class="bi bi-search"></i>
                Buscar
            </button>

        </form>

        <!-- TABLA DE BAUTISMOS -->

        <div class="bautismos-tabla-contenedor">

            <table class="bautismos-tabla">

                <thead>

                    <tr>
                        <th>Solicitud</th>
                        <th>Fecha programada</th>
                        <th>Hora</th>
                        <th>Estado de cita</th>
                        <th>Registro</th>
                        <th>Acción</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($solicitudes as $solicitud)

                        @php

                            /*
                            |--------------------------------------------------------------------------
                            | COMPROBAR SI EL BAUTISMO YA ESTÁ REGISTRADO
                            |--------------------------------------------------------------------------
                            */

                            $yaRegistrado =
                                $solicitud->id_bautismo !== null;

                            /*
                            |--------------------------------------------------------------------------
                            | COMPROBAR FECHA PROGRAMADA
                            |--------------------------------------------------------------------------
                            */

                            $fechaDisponible =
                                \Carbon\Carbon::parse(
                                    $solicitud->fecha
                                )
                                ->startOfDay()
                                ->lte(today());

                            /*
                            |--------------------------------------------------------------------------
                            | COMPROBAR ESTADO DE CITA
                            |--------------------------------------------------------------------------
                            */

                            $citaValida = in_array(
                                $solicitud->estado_cita,
                                [
                                    'programada',
                                    'confirmada',
                                    'reprogramada'
                                ],
                                true
                            );

                        @endphp

                        <tr>

                            <!-- FOLIO DE SOLICITUD -->

                            <td>
                                {{ $solicitud->folio }}
                            </td>

                            <!-- FECHA PROGRAMADA -->

                            <td>
                                {{ \Carbon\Carbon::parse(
                                    $solicitud->fecha
                                )->format('d/m/Y') }}
                            </td>

                            <!-- HORA -->

                            <td>
                                {{ substr(
                                    $solicitud->hora_inicio,
                                    0,
                                    5
                                ) }}
                            </td>

                            <!-- ESTADO DE CITA -->

                            <td>
                                {{ ucfirst(
                                    $solicitud->estado_cita
                                ) }}
                            </td>

                            <!-- ESTADO DE REGISTRO -->

                            <td>

                                @if($yaRegistrado)

                                    <span class="bautismos-estado">
                                        <i class="bi bi-check-circle"></i>
                                        Registrado
                                    </span>

                                @else

                                    <span class="bautismos-pendiente">
                                        Pendiente de registro
                                    </span>

                                @endif

                            </td>

                            <!-- ACCIONES -->

                            <td>

                                @if($yaRegistrado)

                                    <div class="bautismos-acciones">

                                        <!-- FOLIO DEL ACTA -->

                                        <div class="bautismos-folio">

                                            <strong>Acta:</strong>

                                            {{ $solicitud->folio_acta }}

                                        </div>

                                        <!-- DESCARGAR CERTIFICADO -->

                                        @if($esAdministrativo)

                                            <a
                                                href="{{ route(
                                                    'certificados.bautismos.descargar',
                                                    [
                                                        'idBautismo' =>
                                                            $solicitud->id_bautismo
                                                    ]
                                                ) }}"
                                                class="bautismos-boton bautismos-descargar"
                                                title="Descargar certificado de bautismo"
                                            >
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                Descargar certificado PDF
                                            </a>

                                        @endif

                                    </div>

                                @elseif($fechaDisponible && $citaValida)

                                    @if($esAdministrativo)

                                        <a
                                            href="{{ route(
                                                'bautismos.crear',
                                                $solicitud->id_solicitud
                                            ) }}"
                                            class="bautismos-boton"
                                        >
                                            <i class="bi bi-journal-check"></i>
                                            Registrar bautismo
                                        </a>

                                    @else

                                        <span class="bautismos-no-disponible">
                                            Sin permisos
                                        </span>

                                    @endif

                                @else

                                    <span class="bautismos-no-disponible">
                                        No disponible
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="bautismos-vacio"
                            >
                                No hay solicitudes aprobadas
                                o bautismos registrados.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINACIÓN -->

        <div class="bautismos-paginacion">
            {{ $solicitudes->links() }}
        </div>

    </div>

</div>

@endsection
