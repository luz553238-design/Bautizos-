
@extends('layouts.app')

@section('content')

<style>
    .historial-container {
        max-width: 1250px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .historial-titulo {
        color: #704092;
        font-size: 30px;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .historial-descripcion {
        color: #716879;
        margin-bottom: 25px;
    }

    .historial-resumen {
        background: linear-gradient(120deg, #704092, #9971b2);
        color: white;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 25px;
    }

    .historial-resumen h2 {
        font-size: 32px;
        margin: 8px 0 0;
    }

    .historial-card {
        background: white;
        border: 1px solid #e8dfeb;
        border-radius: 15px;
        padding: 25px;
    }

    .historial-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
        align-items: end;
    }

    .historial-campo {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .historial-campo label {
        color: #65417e;
        font-weight: 600;
        font-size: 13px;
    }

    .historial-campo input {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 11px;
        font-size: 14px;
    }

    .historial-buscar {
        flex: 1;
        min-width: 220px;
    }

    .historial-boton {
        display: inline-block;
        background: #79527e;
        color: white;
        padding: 11px 15px;
        border-radius: 8px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }

    .historial-boton:hover {
        background: #603e66;
        color: white;
    }

    .historial-limpiar {
        background: #777;
    }

    .historial-limpiar:hover {
        background: #555;
    }

    .historial-descargar {
        background: #28734b;
    }

    .historial-descargar:hover {
        background: #1c5738;
    }

    .historial-tabla-contenedor {
        overflow-x: auto;
    }

    .historial-tabla {
        width: 100%;
        border-collapse: collapse;
    }

    .historial-tabla th,
    .historial-tabla td {
        padding: 13px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .historial-tabla th {
        background: #f5edf8;
        color: #65417e;
        white-space: nowrap;
    }

    .historial-tabla td {
        color: #51465a;
        font-size: 13px;
    }

    .historial-folio {
        font-weight: 700;
        color: #704092;
    }

    .historial-vacio {
        text-align: center !important;
        padding: 30px !important;
        color: #777;
    }

    .historial-volver {
        display: inline-block;
        margin-bottom: 20px;
        color: #704092;
        text-decoration: none;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .historial-card {
            padding: 15px;
        }

        .historial-titulo {
            font-size: 24px;
        }

        .historial-tabla {
            min-width: 950px;
        }
    }
</style>

<div class="historial-container">

    <a
        href="{{ route('bautismos.index') }}"
        class="historial-volver"
    >
        ← Volver al registro de bautismos
    </a>

    <h1 class="historial-titulo">
        Historial de bautismos
    </h1>

    <p class="historial-descripcion">
        Consulta los bautismos realizados, busca por nombre
        o folio y descarga sus certificados.
    </p>

    <div class="historial-resumen">
        <div>Total de bautismos registrados</div>
        <h2>{{ $totalBautismos }}</h2>
    </div>

    <div class="historial-card">

        <form
            method="GET"
            action="{{ route('bautismos.historial') }}"
            class="historial-filtros"
        >

            <div class="historial-campo historial-buscar">
                <label>Buscar bautismo</label>

                <input
                    type="text"
                    name="buscar"
                    value="{{ $buscar }}"
                    placeholder="Nombre, folio de acta o solicitud"
                >
            </div>

            <div class="historial-campo">
                <label>Fecha desde</label>

                <input
                    type="date"
                    name="fecha_desde"
                    value="{{ $fechaDesde }}"
                >
            </div>

            <div class="historial-campo">
                <label>Fecha hasta</label>

                <input
                    type="date"
                    name="fecha_hasta"
                    value="{{ $fechaHasta }}"
                >
            </div>

            <button
                type="submit"
                class="historial-boton"
            >
                Buscar
            </button>

            <a
                href="{{ route('bautismos.historial') }}"
                class="historial-boton historial-limpiar"
            >
                Limpiar
            </a>

        </form>

        @if($errors->any())
            <div style="color:#b91c1c; margin-bottom:15px;">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="historial-tabla-contenedor">

            <table class="historial-tabla">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bautizado</th>
                        <th>Folio del acta</th>
                        <th>Folio de solicitud</th>
                        <th>Fecha de bautismo</th>
                        <th>Libro</th>
                        <th>Página</th>
                        <th>Certificado</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($bautismos as $bautismo)

                        <tr>

                            <td>
                                {{ $bautismo->id_bautismo }}
                            </td>

                            <td>
                                {{ trim(
                                    $bautismo->nombre . ' ' .
                                    $bautismo->apellido_paterno . ' ' .
                                    $bautismo->apellido_materno
                                ) }}
                            </td>

                            <td>
                                <span class="historial-folio">
                                    {{ $bautismo->folio_acta }}
                                </span>
                            </td>

                            <td>
                                {{ $bautismo->folio_solicitud }}
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse(
                                    $bautismo->fecha_realizacion
                                )->format('d/m/Y') }}
                            </td>

                            <td>
                                {{ $bautismo->numero_libro }}
                            </td>

                            <td>
                                {{ $bautismo->numero_pagina }}
                            </td>

                            <td>
                                <a
                                    href="{{ route(
                                        'certificados.bautismos.descargar',
                                        ['idBautismo' => $bautismo->id_bautismo]
                                    ) }}"
                                    class="historial-boton historial-descargar"
                                >
                                    Descargar PDF
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="historial-vacio">
                                No se encontraron bautismos registrados.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">
            {{ $bautismos->links() }}
        </div>

    </div>

</div>

@endsection
