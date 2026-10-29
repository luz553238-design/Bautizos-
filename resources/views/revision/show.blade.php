
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Detalle de solicitud | Sistema de Bautizos</title>

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
            max-width: 1150px;
            margin: 35px auto;
            padding: 20px;
        }

        .cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            color: #67378d;
            margin: 0 0 8px;
            font-size: 28px;
        }

        h2 {
            color: #67378d;
            font-size: 20px;
            margin: 0 0 20px;
        }

        .subtitulo {
            margin: 0;
            color: #786a84;
            overflow-wrap: anywhere;
        }

        .tarjeta {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 22px;
            box-shadow: 0 5px 20px rgba(65, 34, 92, .06);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .campo {
            min-width: 0;
        }

        .etiqueta {
            display: block;
            font-size: 12px;
            color: #81738d;
            margin-bottom: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .valor {
            font-size: 15px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .estado {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
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

        .boton {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 9px;
            border: 0;
            background: #75409e;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            transition: .2s;
        }

        .boton:hover {
            filter: brightness(.92);
        }

        .boton-secundario {
            background: #e8dcef;
            color: #63328b;
        }

        .boton-aprobar {
            background: #15803d;
        }

        .boton-rechazar {
            background: #b91c1c;
        }

        .boton-documento {
            background: #eef0ff;
            color: #4338ca;
        }

        .mensaje {
            padding: 15px;
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

        .lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .elemento {
            padding: 16px;
            border: 1px solid #eee7f2;
            border-radius: 11px;
        }

        .elemento-titulo {
            font-weight: bold;
            color: #563276;
            margin-bottom: 8px;
        }

        .elemento-detalle {
            color: #786a84;
            font-size: 14px;
            line-height: 1.7;
        }

        .documento {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .acciones {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .acciones form {
            margin: 0;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #594766;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d9cce4;
            border-radius: 9px;
            font: inherit;
            color: #352641;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #75409e;
            box-shadow: 0 0 0 3px rgba(117, 64, 158, .12);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .formulario-campo {
            margin-bottom: 18px;
        }

        .nota {
            background: #f7f1fb;
            border-left: 4px solid #75409e;
            padding: 14px;
            border-radius: 8px;
            color: #624e70;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .separador {
            height: 1px;
            background: #eee7f2;
            margin: 25px 0;
        }

        .sin-registros {
            color: #786a84;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            .contenedor {
                margin: 12px auto;
                padding: 12px;
            }

            .tarjeta {
                padding: 18px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 23px;
            }

            .acciones,
            .acciones form,
            .acciones .boton {
                width: 100%;
            }
        }
    </style>
</head>

<body>

@php
    $estado = mb_strtolower(
        trim((string) ($solicitud->estado ?? 'pendiente'))
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

    $puedeResolver = $estado === 'pendiente';
@endphp

<div class="contenedor">

    <!-- ENCABEZADO -->

    <div class="cabecera">

        <div>
            <h1>Detalle de solicitud</h1>
            <p class="subtitulo">
                Folio: {{ $solicitud->folio }}
            </p>
        </div>

        <a
            href="{{ route('revision.index') }}"
            class="boton boton-secundario"
        >
            Volver a solicitudes
        </a>

    </div>

    <!-- MENSAJES -->

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
            <strong>Corrige los siguientes errores:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- INFORMACIÓN GENERAL -->

    <div class="tarjeta">

        <h2>Información general</h2>

        <div class="grid">

            <div class="campo">
                <span class="etiqueta">Folio</span>
                <div class="valor">
                    {{ $solicitud->folio }}
                </div>
            </div>

            <div class="campo">
                <span class="etiqueta">Estado</span>
                <span class="estado {{ $claseEstado }}">
                    {{ ucfirst($solicitud->estado) }}
                </span>
            </div>

            <div class="campo">
                <span class="etiqueta">Fecha de solicitud</span>
                <div class="valor">
                    {{ $solicitud->fecha_solicitud ?? 'No disponible' }}
                </div>
            </div>

            <div class="campo">
                <span class="etiqueta">Fecha de ceremonia</span>
                <div class="valor">
                    {{ $solicitud->fecha ?? 'Por definir' }}
                </div>
            </div>

            <div class="campo">
                <span class="etiqueta">Horario</span>
                <div class="valor">
                    {{ $solicitud->hora_inicio
                        ? substr($solicitud->hora_inicio, 0, 5)
                        : 'Por definir' }}
                </div>
            </div>

            <div class="campo">
                <span class="etiqueta">Observaciones</span>
                <div class="valor">
                    {{ $solicitud->observaciones ?: 'Sin observaciones' }}
                </div>
            </div>

        </div>

    </div>

    <!-- DATOS DEL MENOR -->

    <div class="tarjeta">

        <h2>Datos del menor</h2>

        <div class="grid">

            <div class="campo">
                <span class="etiqueta">Nombre completo</span>
                <div class="valor">
                    {{ $nombreMenor }}
                </div>
            </div>

            <div class="campo">
                <span class="etiqueta">Fecha de nacimiento</span>
                <div class="valor">
                    {{ $solicitud->fecha_nacimiento ?? 'No disponible' }}
                </div>
            </div>

            <div class="campo">
                <span class="etiqueta">Lugar de nacimiento</span>
                <div class="valor">
                    {{ $solicitud->lugar_nacimiento ?? 'No disponible' }}
                </div>
            </div>

        </div>

    </div>

    <!-- PADRES Y TUTORES -->

    <div class="tarjeta">

        <h2>Padres y tutores</h2>

        <div class="lista">

            @forelse($tutores as $tutor)

                <div class="elemento">

                    <div class="elemento-titulo">
                        {{ trim(
                            $tutor->nombre . ' ' .
                            $tutor->apellido_paterno . ' ' .
                            ($tutor->apellido_materno ?? '')
                        ) }}
                    </div>

                    <div class="elemento-detalle">
                        Parentesco: {{ $tutor->parentesco ?? 'No especificado' }}
                        <br>
                        Teléfono: {{ $tutor->telefono ?? 'No registrado' }}
                        <br>
                        Tipo:
                        {{ $tutor->tutor_principal
                            ? 'Responsable principal'
                            : 'Segundo tutor' }}
                    </div>

                </div>

            @empty

                <p class="sin-registros">
                    No se encontraron tutores registrados.
                </p>

            @endforelse

        </div>

    </div>

    <!-- PADRINOS -->

    <div class="tarjeta">

        <h2>Padrinos</h2>

        <div class="lista">

            @forelse($padrinos as $padrino)

                <div class="elemento">

                    <div class="elemento-titulo">
                        {{ trim(
                            $padrino->nombre . ' ' .
                            $padrino->apellido_paterno . ' ' .
                            ($padrino->apellido_materno ?? '')
                        ) }}
                    </div>

                    <div class="elemento-detalle">
                        Participación:
                        {{ ucfirst($padrino->tipo_participacion) }}
                        <br>
                        Estado civil:
                        {{ $padrino->estado_civil ?? 'No registrado' }}
                        <br>
                        Teléfono:
                        {{ $padrino->telefono ?? 'No registrado' }}
                    </div>

                </div>

            @empty

                <p class="sin-registros">
                    No se encontraron padrinos registrados.
                </p>

            @endforelse

        </div>

    </div>

    <!-- DOCUMENTOS -->

    <div class="tarjeta">

        <h2>Documentos PDF</h2>

        <p class="nota">
            Los documentos se almacenan de forma privada.
            Utiliza el botón Descargar PDF para revisarlos.
        </p>

        <div class="lista">

            @forelse($documentos as $documento)

                <div class="elemento documento">

                    <div>

                        <div class="elemento-titulo">
                            {{ $documento->tipo_documento }}
                        </div>

                        <div class="elemento-detalle">
                            Archivo:
                            {{ $documento->nombre_archivo }}
                            <br>
                            Estado de revisión:
                            {{ ucfirst($documento->estado_revision ?? 'pendiente') }}
                        </div>

                    </div>

                    <a
                        href="{{ route(
                            'solicitudes.documentos.descargar',
                            $documento->id_documento
                        ) }}"
                        class="boton boton-documento"
                    >
                        Descargar PDF
                    </a>

                </div>

            @empty

                <p class="sin-registros">
                    Esta solicitud no tiene documentos registrados.
                </p>

            @endforelse

        </div>

    </div>

    <!-- PLÁTICA PREBAUTISMAL -->

    <div class="tarjeta">

        <h2>Plática prebautismal</h2>

        @if($platica)

            <div class="grid">

                <div class="campo">
                    <span class="etiqueta">Fecha</span>
                    <div class="valor">
                        {{ $platica->fecha }}
                    </div>
                </div>

                <div class="campo">
                    <span class="etiqueta">Horario</span>
                    <div class="valor">
                        {{ substr($platica->hora_inicio, 0, 5) }}
                        -
                        {{ substr($platica->hora_fin, 0, 5) }}
                    </div>
                </div>

                <div class="campo">
                    <span class="etiqueta">Lugar</span>
                    <div class="valor">
                        {{ $platica->lugar ?? 'Por definir' }}
                    </div>
                </div>

                <div class="campo">
                    <span class="etiqueta">Estado de inscripción</span>
                    <div class="valor">
                        {{ ucfirst($platica->estado ?? 'No disponible') }}
                    </div>
                </div>

            </div>

        @else

            <p class="sin-registros">
                No se encontró una plática prebautismal asociada.
            </p>

        @endif

    </div>

    <!-- APROBAR O RECHAZAR -->

    <div class="tarjeta">

        <h2>Resolución de la solicitud</h2>

        @if($puedeResolver)

            <p class="nota">
                Antes de aprobar, revisa los datos del menor,
                la información de los tutores y padrinos,
                los documentos PDF y la plática prebautismal.
            </p>

            <div class="acciones">

                <form
                    action="{{ route('revision.aprobar', $solicitud->id_solicitud) }}"
                    method="POST"
                    onsubmit="return confirm('¿Deseas aprobar esta solicitud de bautismo?');"
                >
                    @csrf

                    <button
                        type="submit"
                        class="boton boton-aprobar"
                    >
                        Aprobar solicitud
                    </button>
                </form>

            </div>

            <div class="separador"></div>

            <h2>Rechazar solicitud</h2>

            <form
                action="{{ route('revision.rechazar', $solicitud->id_solicitud) }}"
                method="POST"
                onsubmit="return confirm('¿Estás seguro de rechazar esta solicitud?');"
            >
                @csrf

                <div class="formulario-campo">

                    <label for="motivo_rechazo">
                        Motivo del rechazo
                    </label>

                    <textarea
                        id="motivo_rechazo"
                        name="motivo"
                        minlength="10"
                        maxlength="1000"
                        required
                        placeholder="Explica por qué se rechaza la solicitud..."
                    >{{ old('motivo') }}</textarea>

                </div>

                <button
                    type="submit"
                    class="boton boton-rechazar"
                >
                    Rechazar solicitud
                </button>

            </form>

        @else

            <p class="nota">
                Esta solicitud tiene el estado
                <strong>{{ $solicitud->estado }}</strong>.
                Las acciones de aprobación y rechazo
                solo están disponibles para solicitudes pendientes.
            </p>

        @endif

    </div>

    <!-- PROPONER CAMBIO DE FECHA -->

    @if($puedeResolver)

        <div class="tarjeta">

            <h2>Proponer cambio de ceremonia</h2>

            <p class="nota">
                Si la fecha u horario original no es adecuado,
                puedes proponer uno nuevo. El cliente deberá
                aceptar o rechazar la propuesta desde su cuenta.
                Se permiten máximo dos bautismos por horario.
            </p>

            <form
                action="{{ route('revision.proponer', $solicitud->id_solicitud) }}"
                method="POST"
            >
                @csrf

                <div class="grid">

                    <div class="formulario-campo">

                        <label for="fecha">
                            Nueva fecha
                        </label>

                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            min="{{ now()->toDateString() }}"
                            value="{{ old('fecha') }}"
                            required
                        >

                    </div>

                    <div class="formulario-campo">

                        <label for="hora">
                            Nuevo horario
                        </label>

                        <select
                            id="hora"
                            name="hora"
                            required
                        >
                            <option value="">
                                Selecciona un horario
                            </option>

                            <option
                                value="11:00"
                                @selected(old('hora') === '11:00')
                            >
                                11:00 a. m.
                            </option>

                            <option
                                value="12:00"
                                @selected(old('hora') === '12:00')
                            >
                                12:00 p. m.
                            </option>

                            <option
                                value="13:00"
                                @selected(old('hora') === '13:00')
                            >
                                1:00 p. m.
                            </option>

                        </select>

                    </div>

                </div>

                <div class="formulario-campo">

                    <label for="motivo_cambio">
                        Motivo del cambio
                    </label>

                    <textarea
                        id="motivo_cambio"
                        name="motivo"
                        minlength="10"
                        maxlength="1000"
                        required
                        placeholder="Explica al cliente por qué propones cambiar la fecha u horario..."
                    >{{ old('motivo') }}</textarea>

                </div>

                <button type="submit" class="boton">
                    Enviar propuesta al cliente
                </button>

            </form>

        </div>

    @endif

</div>

</body>
</html>
