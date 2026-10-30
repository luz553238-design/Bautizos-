
@extends('layouts.app')

@section('content')

<style>
    .registro-container {
        max-width: 800px;
        margin: 35px auto;
        padding: 0 20px;
    }

    .registro-card {
        background: white;
        border-radius: 16px;
        padding: 35px;
        border: 1px solid #e6dfea;
        box-shadow: 0 5px 25px rgba(0,0,0,.04);
    }

    .registro-titulo {
        color: #704092;
        margin-bottom: 10px;
    }

    .registro-descripcion {
        color: #6b6475;
        margin-bottom: 25px;
    }

    .registro-grupo {
        margin-bottom: 20px;
    }

    .registro-grupo label {
        display: block;
        margin-bottom: 8px;
        color: #3c3144;
        font-weight: 600;
    }

    .registro-grupo input,
    .registro-grupo textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #d8c8dc;
        border-radius: 8px;
        box-sizing: border-box;
        font: inherit;
    }

    .registro-grupo textarea {
        min-height: 110px;
        resize: vertical;
    }

    .registro-boton {
        display: inline-block;
        background: #79527e;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
    }

    .registro-boton:hover {
        background: #603e66;
        color: white;
    }

    .registro-secundario {
        background: #eee6f1;
        color: #573765;
    }

    .registro-secundario:hover {
        background: #e1d3e7;
        color: #573765;
    }

    .registro-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .registro-info {
        background: #f5eef8;
        padding: 18px;
        border-radius: 10px;
        margin-bottom: 25px;
        line-height: 1.8;
    }

    .registro-acciones {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
</style>

<div class="registro-container">

    <div class="registro-card">

        <h1 class="registro-titulo">
            Registrar bautismo realizado
        </h1>

        <p class="registro-descripcion">
            Completa los datos oficiales de la ceremonia
            después de confirmar que el bautismo se realizó.
        </p>

        <div class="registro-info">

            <strong>Folio de solicitud:</strong>
            {{ $solicitud->folio }}

            <br>

            <strong>Fecha programada:</strong>
            {{ $solicitud->fecha }}

            <br>

            <strong>Horario:</strong>
            {{ substr($solicitud->hora_inicio, 0, 5) }}

        </div>

        @if($errors->any())

            <div class="registro-error">

                <strong>
                    Corrige los siguientes errores:
                </strong>

                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>

        @endif

        <form
            method="POST"
            action="{{ route('bautismos.guardar', $solicitud->id_solicitud) }}"
            onsubmit="return confirm('¿Confirmas que la ceremonia ya se realizó y deseas registrar oficialmente este bautismo?');"
        >

            @csrf

            <div class="registro-grupo">

                <label for="fecha_realizacion">
                    Fecha real de la ceremonia
                </label>

                <input
                    type="date"
                    id="fecha_realizacion"
                    name="fecha_realizacion"
                    value="{{ old('fecha_realizacion', $solicitud->fecha) }}"
                    min="{{ $solicitud->fecha }}"
                    max="{{ now()->toDateString() }}"
                    required
                >

                @error('fecha_realizacion')
                    <small style="color:#b42318;">
                        {{ $message }}
                    </small>
                @enderror

            </div>

            <div class="registro-grupo">

                <label for="folio_acta">
                    Folio del acta
                </label>

                <input
                    type="text"
                    id="folio_acta"
                    name="folio_acta"
                    maxlength="50"
                    value="{{ old('folio_acta') }}"
                    placeholder="Ejemplo: ACT-2026-0001"
                    required
                >

            </div>

            <div class="registro-grupo">

                <label for="numero_libro">
                    Número de libro
                </label>

                <input
                    type="text"
                    id="numero_libro"
                    name="numero_libro"
                    maxlength="50"
                    value="{{ old('numero_libro') }}"
                    placeholder="Ejemplo: LIB-2026-01"
                    required
                >

            </div>

            <div class="registro-grupo">

                <label for="numero_pagina">
                    Número de página
                </label>

                <input
                    type="text"
                    id="numero_pagina"
                    name="numero_pagina"
                    maxlength="50"
                    value="{{ old('numero_pagina') }}"
                    placeholder="Ejemplo: 15"
                    required
                >

            </div>

            <div class="registro-grupo">

                <label for="observaciones">
                    Observaciones
                </label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    maxlength="5000"
                    placeholder="Observaciones adicionales de la ceremonia"
                >{{ old('observaciones') }}</textarea>

            </div>

            <div class="registro-acciones">

                <button
                    type="submit"
                    class="registro-boton"
                >
                    Guardar bautismo
                </button>

                <a
                    href="{{ route('bautismos.index') }}"
                    class="registro-boton registro-secundario"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</div>

@endsection

