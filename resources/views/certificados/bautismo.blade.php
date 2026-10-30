
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Bautismo</title>

    <style>
        @page {
            size: letter portrait;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111111;
        }

        /*
        |--------------------------------------------------------------------------
        | CONTENEDOR PRINCIPAL
        |--------------------------------------------------------------------------
        | Hoja carta: 215.9 mm de ancho.
        | Margen izquierdo: 6 mm.
        | Ancho exterior: 203 mm.
        | Margen derecho aproximado: 6.9 mm.
        |
        | La altura se mantiene en media hoja carta.
        */

        .certificado {
            position: absolute;
            top: 4mm;
            left: 6mm;

            width: 189mm;
            height: 123mm;

            padding: 4mm 6mm;

            border: 1px solid #333333;

            overflow: hidden;
        }

        /*
        |--------------------------------------------------------------------------
        | ENCABEZADO
        |--------------------------------------------------------------------------
        */

        .diocesis {
            width: 100%;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .parroquia {
            font-size: 10px;
            margin-bottom: 2mm;
            white-space: nowrap;
        }

        .parroquia-nombre {
            display: inline-block;
            width: 75%;
            border-bottom: 1px solid #444444;
            padding-bottom: 2px;
            text-transform: uppercase;
        }

        .titulo {
            width: 100%;
            text-align: center;
            font-size: 21px;
            font-weight: bold;
            margin: 2mm 0 3mm;
        }

        /*
        |--------------------------------------------------------------------------
        | MARCA DE PRUEBA
        |--------------------------------------------------------------------------
        */

        .marca-prueba {
            width: 98%;
            border: 1px solid #b42318;
            color: #b42318;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            padding: 1.3mm 0;
            margin-bottom: 3mm;
        }

        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN DEL BAUTIZADO
        |--------------------------------------------------------------------------
        */

        .fila {
            width: 100%;
            font-size: 11px;
            margin-bottom: 2.5mm;
            white-space: nowrap;
        }

        .etiqueta {
            font-weight: bold;
        }

        .valor {
            display: inline-block;
            border-bottom: 1px solid #444444;
            text-align: center;
            text-transform: uppercase;
            padding-bottom: 2px;
        }

        .nombre-nino {
            width: 73%;
            font-size: 11px;
            font-weight: bold;
        }

        .texto-sacramento {
            width: 100%;
            font-size: 10px;
            line-height: 1.4;
            margin: 2mm 0 3mm;
        }

        /*
        |--------------------------------------------------------------------------
        | FECHAS
        |--------------------------------------------------------------------------
        */

        .fecha {
            width: 100%;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3mm;
            white-space: nowrap;
        }

        .fecha-valor {
            display: inline-block;
            border-bottom: 1px solid #444444;
            text-align: center;
            min-width: 36px;
            padding: 0 4px 2px;
            font-weight: normal;
        }

        .fecha-mes {
            min-width: 100px;
        }

        .lugar {
            width: 75%;
        }

        /*
        |--------------------------------------------------------------------------
        | PADRES Y PADRINOS
        |--------------------------------------------------------------------------
        */

        .seccion {
            width: 100%;
            font-size: 11px;
            font-weight: bold;
            margin: 3mm 0 1mm;
        }

        .persona-linea {
            width: 98%;
            font-size: 10px;
            height: 5mm;
            line-height: 4mm;
            padding-left: 3mm;
            border-bottom: 1px solid #555555;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | ESPACIOS PARA FIRMAS
        |--------------------------------------------------------------------------
        */

        .firmas {
            width: 98%;
            border-collapse: collapse;
            margin-top: 4mm;
            table-layout: fixed;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            padding: 0 3mm;
            vertical-align: bottom;
        }

        .firma-titulo {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5mm;
        }

        .firma-linea {
            border-top: 1px solid #444444;
            font-size: 9px;
            padding-top: 1mm;
        }

        /*
        |--------------------------------------------------------------------------
        | DATOS DEL REGISTRO
        |--------------------------------------------------------------------------
        */

        .datos-registro {
            width: 98%;
            margin-top: 3mm;
            padding-top: 1.5mm;
            border-top: 1px solid #bbbbbb;
            text-align: center;
            font-size: 9px;
            white-space: nowrap;
        }

        .datos-registro span {
            margin: 0 2mm;
        }
    </style>
</head>

<body>

@php
    $fechaBautismo = \Carbon\Carbon::parse(
        $bautismo->fecha_realizacion
    )->locale('es');

    $fechaNacimiento = !empty($bautismo->fecha_nacimiento)
        ? \Carbon\Carbon::parse(
            $bautismo->fecha_nacimiento
        )->locale('es')
        : null;

    $nombreDiocesis = $diocesis
        ?? 'Diócesis de Toluca, A.R.';

    $nombreParroquia = $parroquia
        ?? 'La Asunción de María, Pipioltepec';

    $nombresPadres = collect($tutores)
        ->pluck('nombre')
        ->values();

    $nombresPadrinos = collect($padrinos)
        ->pluck('nombre')
        ->values();
@endphp

<div class="certificado">

    <!-- ENCABEZADO -->

    <div class="diocesis">
        {{ $nombreDiocesis }}
    </div>

    <div class="parroquia">
        <strong>Parroquia de</strong>

        <span class="parroquia-nombre">
            {{ $nombreParroquia }}
        </span>
    </div>

    <div class="titulo">
        Certificado de Bautismo
    </div>


    <!-- NOMBRE DEL BAUTIZADO -->

    <div class="fila">
        <span class="etiqueta">
            El (la) Niño(a)
        </span>

        <span class="valor nombre-nino">
            {{ $nombreBautizado }}
        </span>
    </div>

    <!-- TEXTO DEL SACRAMENTO -->

    <div class="texto-sacramento">
        Fue bautizado(a) y así renació por el Agua
        y el Espíritu Santo y se unió a la Iglesia,
        Nuevo Pueblo de Dios.
    </div>

    <!-- FECHA DEL BAUTISMO -->

    <div class="fecha">
        El día

        <span class="fecha-valor">
            {{ $fechaBautismo->format('d') }}
        </span>

        de

        <span class="fecha-valor fecha-mes">
            {{ mb_strtoupper(
                $fechaBautismo->translatedFormat('F')
            ) }}
        </span>

        de

        <span class="fecha-valor">
            {{ $fechaBautismo->format('Y') }}
        </span>
    </div>

    <!-- LUGAR DE NACIMIENTO -->

    <div class="fila">
        <span class="etiqueta">
            Nació en
        </span>

        <span class="valor lugar">
            {{ $bautismo->lugar_nacimiento ?? 'NO REGISTRADO' }}
        </span>
    </div>

    <!-- FECHA DE NACIMIENTO -->

    <div class="fecha">
        El día

        <span class="fecha-valor">
            {{ $fechaNacimiento
                ? $fechaNacimiento->format('d')
                : '--' }}
        </span>

        de

        <span class="fecha-valor fecha-mes">
            {{ $fechaNacimiento
                ? mb_strtoupper(
                    $fechaNacimiento->translatedFormat('F')
                )
                : 'NO REGISTRADO' }}
        </span>

        de

        <span class="fecha-valor">
            {{ $fechaNacimiento
                ? $fechaNacimiento->format('Y')
                : '----' }}
        </span>
    </div>

    <!-- PADRES -->

    <div class="seccion">
        Se comprometieron a educarlo en la Fe, sus Padres:
    </div>

    <div class="persona-linea">
        {{ $nombresPadres->get(0, '') }}
    </div>

    <div class="persona-linea">
        {{ $nombresPadres->get(1, '') }}
    </div>

    <!-- PADRINOS -->

    <div class="seccion">
        Aceptaron el mismo compromiso sus Padrinos:
    </div>

    <div class="persona-linea">
        {{ $nombresPadrinos->get(0, '') }}
    </div>

    <div class="persona-linea">
        {{ $nombresPadrinos->get(1, '') }}
    </div>

    <!-- ESPACIOS PARA FIRMAS -->

    <table class="firmas">
        <tr>
            <td>
                <div class="firma-titulo">
                    Lo Bautizó
                </div>

                <div class="firma-linea">
                    Nombre del celebrante
                </div>
            </td>

            <td>
                <div class="firma-titulo">
                    El Párroco actual
                </div>

                <div class="firma-linea">
                    Nombre del párroco
                </div>
            </td>
        </tr>
    </table>

    <!-- DATOS DEL ACTA -->

    <div class="datos-registro">
        <span>
            <strong>Folio:</strong>
            {{ $bautismo->folio_acta }}
        </span>

        <span>
            <strong>Libro:</strong>
            {{ $bautismo->numero_libro }}
        </span>

        <span>
            <strong>Página:</strong>
            {{ $bautismo->numero_pagina }}
        </span>
    </div>

</div>

</body>
</html>
