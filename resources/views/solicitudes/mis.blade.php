
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Mis solicitudes de bautismo</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f1fa;
            color: #352641;
        }

        .contenedor {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        h1 {
            color: #67378d;
            margin: 0;
        }

        .cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .boton {
            display: inline-block;
            background: #75409e;
            color: white;
            padding: 12px 18px;
            border-radius: 9px;
            text-decoration: none;
            border: 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s;
        }

        .boton:hover {
            background: #5b2e80;
        }

        .boton-secundario {
            background: #e8dcef;
            color: #63328b;
        }

        .boton-secundario:hover {
            background: #d9c5e8;
        }

        .tarjeta {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 22px;
            overflow-x: auto;
            box-shadow: 0 6px 20px rgba(53, 34, 77, 0.06);
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 750px;
        }

        th,
        td {
            padding: 16px 14px;
            border-bottom: 1px solid #e8dfee;
            text-align: left;
            vertical-align: middle;
        }

        th {
            color: #63328b;
            font-weight: bold;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        tbody tr:hover {
            background: #faf7fc;
        }

        .folio {
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .estado {
            display: inline-block;
            border-radius: 20px;
            padding: 7px 13px;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
        }

        .estado-pendiente {
            background: #fff0cf;
            color: #885c00;
        }

        .estado-aprobado,
        .estado-autorizado,
        .estado-confirmado {
            background: #def5e5;
            color: #166534;
        }

        .estado-rechazado,
        .estado-cancelado {
            background: #fee2e2;
            color: #991b1b;
        }

        .estado-revision,
        .estado-en_revision {
            background: #dbeafe;
            color: #1e40af;
        }

        .estado-default {
            background: #ede9f0;
            color: #594766;
        }

        .mensaje {
            background: #e4f7e9;
            color: #166534;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            margin-bottom: 20px;
            border: 1px solid #bbebc7;
        }

        .mensaje-error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        .sin-registros {
            text-align: center;
            padding: 35px 15px;
            color: #776983;
        }

        .paginacion {
            margin-top: 20px;
        }

        .acciones-inferiores {
            margin-top: 25px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 650px) {
            .contenedor {
                margin: 15px auto;
                padding: 12px;
            }

            .cabecera {
                align-items: stretch;
            }

            .cabecera h1 {
                font-size: 24px;
            }

            .tarjeta {
                padding: 15px;
            }

            .acciones-inferiores .boton {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <!-- ENCABEZADO -->

    <div class="cabecera">

        <h1>Mis solicitudes de bautismo</h1>

        <a
            href="{{ route('solicitudes.crear') }}"
            class="boton"
        >
            + Nueva solicitud
        </a>

    </div>

    <!-- MENSAJE DE ÉXITO -->

    @if(session('success'))

        <div class="mensaje">
            {{ session('success') }}
        </div>

    @endif

    <!-- MENSAJE DE ERROR -->

    @if(session('error'))

        <div class="mensaje mensaje-error">
            {{ session('error') }}
        </div>

    @endif

    <!-- TABLA DE SOLICITUDES -->

    <div class="tarjeta">

        <table>

            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha de solicitud</th>
                    <th>Ceremonia</th>
                    <th>Horario</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>

                @forelse($solicitudes as $solicitud)

                    @php
                        $estado = strtolower(
                            trim((string) ($solicitud->estado ?? 'pendiente'))
                        );

                        $claseEstado = match ($estado) {
                            'pendiente' => 'estado-pendiente',

                            'aprobado',
                            'autorizado',
                            'confirmado' => 'estado-aprobado',

                            'rechazado',
                            'cancelado' => 'estado-rechazado',

                            'revision',
                            'en_revision',
                            'en revisión' => 'estado-revision',

                            default => 'estado-default',
                        };
                    @endphp

                    <tr>

                        <!-- FOLIO -->

                        <td class="folio">
                            {{ $solicitud->folio }}
                        </td>

                        <!-- FECHA DE SOLICITUD -->

                        <td>
                            {{ $solicitud->fecha_solicitud ?? 'No disponible' }}
                        </td>

                        <!-- FECHA DE CEREMONIA -->

                        <td>
                            {{ $solicitud->fecha ?? 'Por definir' }}
                        </td>

                        <!-- HORARIO -->

                        <td>
                            {{ $solicitud->hora_inicio ?? 'Por definir' }}
                        </td>

                        <!-- ESTADO -->

                        <td>

                            <span class="estado {{ $claseEstado }}">

                                {{ ucfirst(str_replace('_', ' ', $estado)) }}

                            </span>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="sin-registros">

                            Todavía no tienes solicitudes registradas.

                            <p>
                                Puedes comenzar creando una nueva solicitud
                                de bautismo.
                            </p>

                            <a
                                href="{{ route('solicitudes.crear') }}"
                                class="boton"
                            >
                                Crear mi primera solicitud
                            </a>

                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

        <!-- PAGINACIÓN -->

        @if(method_exists($solicitudes, 'links'))

            <div class="paginacion">
                {{ $solicitudes->links() }}
            </div>

        @endif

    </div>

    <!-- BOTONES INFERIORES -->

    <div class="acciones-inferiores">

        <a
            href="{{ route('solicitudes.menu') }}"
            class="boton"
        >
            Volver al menú
        </a>

        <a
            href="{{ route('solicitudes.crear') }}"
            class="boton boton-secundario"
        >
            + Nueva solicitud
        </a>

    </div>

</div>

</body>
</html>
