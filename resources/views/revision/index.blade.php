
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Revisión de solicitudes | Sistema de Bautizos</title>

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
            max-width: 1250px;
            margin: 35px auto;
            padding: 20px;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        h1 {
            color: #67378d;
            margin: 0 0 8px;
            font-size: 29px;
        }

        .subtitulo {
            color: #786a84;
            margin: 0;
        }

        .tarjeta {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 22px;
            box-shadow: 0 5px 20px rgba(65, 34, 92, .06);
        }

        .buscador {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .buscador input {
            flex: 1;
            min-width: 220px;
            padding: 13px 15px;
            border: 1px solid #d9cce4;
            border-radius: 9px;
            font-size: 14px;
            outline: none;
        }

        .buscador input:focus {
            border-color: #75409e;
            box-shadow: 0 0 0 3px rgba(117, 64, 158, .12);
        }

        .boton {
            display: inline-block;
            background: #75409e;
            color: white;
            border: 0;
            padding: 12px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: .2s;
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

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 16px 12px;
            border-bottom: 1px solid #eee7f2;
            text-align: left;
            vertical-align: middle;
            font-size: 14px;
        }

        th {
            color: #63328b;
            background: #faf7fc;
            font-weight: bold;
        }

        tbody tr:hover {
            background: #fcf9fe;
        }

        .folio {
            font-weight: bold;
            color: #59317b;
            overflow-wrap: anywhere;
        }

        .estado {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .pendiente {
            background: #fff0cf;
            color: #885c00;
        }

        .aprobada {
            background: #dcfce7;
            color: #166534;
        }

        .rechazada {
            background: #fee2e2;
            color: #991b1b;
        }

        .otro {
            background: #e9e4ee;
            color: #574766;
        }

        .mensaje {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .mensaje-exito {
            background: #e4f7e9;
            color: #166534;
        }

        .mensaje-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .vacio {
            text-align: center;
            padding: 35px;
            color: #786a84;
        }

        .paginacion {
            margin-top: 22px;
        }

        .pie {
            margin-top: 25px;
        }

        @media (max-width: 650px) {
            .contenedor {
                margin: 12px auto;
                padding: 12px;
            }

            h1 {
                font-size: 24px;
            }

            .tarjeta {
                padding: 15px;
            }

            .buscador .boton {
                flex: 1;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <div class="encabezado">

        <div>
            <h1>Revisión de solicitudes</h1>
            <p class="subtitulo">
                Consulta y administra las solicitudes de bautismo registradas.
            </p>
        </div>

        <a href="{{ route('dashboard') }}" class="boton boton-secundario">
            Volver al panel
        </a>

    </div>

    @if(session('success'))
        <div class="mensaje mensaje-exito">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mensaje mensaje-error">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mensaje mensaje-error">
            <strong>Se encontraron errores:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tarjeta">

        <form
            action="{{ route('revision.index') }}"
            method="GET"
            class="buscador"
        >

            <input
                type="search"
                name="buscar"
                value="{{ $buscar ?? '' }}"
                placeholder="Buscar por folio o nombre del menor..."
                aria-label="Buscar solicitudes"
            >

            <button type="submit" class="boton">
                Buscar
            </button>

            <a
                href="{{ route('revision.index') }}"
                class="boton boton-secundario"
            >
                Limpiar
            </a>

        </form>

    </div>

    <div class="tarjeta">

        <div class="tabla-contenedor">

            <table>

                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Menor</th>
                        <th>Fecha de solicitud</th>
                        <th>Ceremonia</th>
                        <th>Horario</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($solicitudes as $solicitud)

                    @php
                        $estado = mb_strtolower(
                            trim((string) $solicitud->estado)
                        );

                        $claseEstado = match ($estado) {
                            'pendiente' => 'pendiente',
                            'aprobada', 'aprobado', 'confirmada' => 'aprobada',
                            'rechazada', 'rechazado', 'cancelada' => 'rechazada',
                            default => 'otro',
                        };

                        $nombreMenor = trim(
                            ($solicitud->nombre ?? '') . ' ' .
                            ($solicitud->apellido_paterno ?? '') . ' ' .
                            ($solicitud->apellido_materno ?? '')
                        );
                    @endphp

                    <tr>

                        <td class="folio">
                            {{ $solicitud->folio }}
                        </td>

                        <td>
                            {{ $nombreMenor }}
                        </td>

                        <td>
                            {{ $solicitud->fecha_solicitud ?? 'No disponible' }}
                        </td>

                        <td>
                            {{ $solicitud->fecha ?? 'Por definir' }}
                        </td>

                        <td>
                            {{ $solicitud->hora_inicio
                                ? substr($solicitud->hora_inicio, 0, 5)
                                : 'Por definir' }}
                        </td>

                        <td>
                            <span class="estado {{ $claseEstado }}">
                                {{ ucfirst($solicitud->estado) }}
                            </span>
                        </td>

                        <td>
                            <a
                                href="{{ route('revision.show', $solicitud->id_solicitud) }}"
                                class="boton"
                            >
                                Revisar
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7" class="vacio">
                            No se encontraron solicitudes de bautismo.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        @if(method_exists($solicitudes, 'links'))
            <div class="paginacion">
                {{ $solicitudes->links() }}
            </div>
        @endif

    </div>

</div>

</body>
</html>
