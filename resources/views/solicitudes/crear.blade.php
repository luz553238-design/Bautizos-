@php
    $opcionesEstadoCivil = collect($estadosCiviles ?? [])
        ->map(function ($estado) {
            if (is_string($estado)) {
                return $estado;
            }

            if (is_array($estado)) {
                return $estado['nombre'] ?? $estado['estado_civil'] ?? null;
            }

            return $estado->nombre ?? $estado->estado_civil ?? null;
        })
        ->filter()
        ->unique()
        ->values();

    if ($opcionesEstadoCivil->isEmpty()) {
        $opcionesEstadoCivil = collect([
            'Soltero',
            'Casado',
            'Viudo',
            'Divorciado',
            'Unión libre',
            'Separado'
        ]);
    }

    $lugaresNacimiento = [
        'Valle de Bravo',
        'Amanalco',
        'Donato Guerra',
        'Villa de Allende',
        'Villa Victoria',
        'Santo Tomás',
        'Ixtapan del Oro',
        'Temascaltepec',
        'Tejupilco',
        'Toluca',
        'Metepec',
        'Zinacantepec',
        'Ciudad de México'
    ];

    $lugarAnterior = old('menor.lugar_nacimiento', '');

    $lugarEsOtro = $lugarAnterior !== ''
        && !in_array($lugarAnterior, $lugaresNacimiento, true);
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Nueva solicitud de bautismo</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f1fa;
            color: #30233e;
            font-family: Arial, sans-serif;
        }

        .contenedor {
            max-width: 1050px;
            margin: 35px auto;
            padding: 20px;
        }

        .cabecera {
            background: linear-gradient(120deg, #63328b, #9862bd);
            color: white;
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 22px;
        }

        .cabecera h1 {
            margin: 0 0 10px;
        }

        .cabecera p {
            margin: 0;
            opacity: .9;
            line-height: 1.6;
        }

        .tarjeta {
            background: white;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 8px 25px #35224d12;
        }

        .pasos {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .indicador {
            height: 7px;
            flex: 1;
            background: #e5d9ee;
            border-radius: 8px;
        }

        .indicador.activo {
            background: #7844a2;
        }

        .numero-paso {
            color: #75469a;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .seccion {
            display: none;
        }

        .seccion.activa {
            display: block;
        }

        .seccion h2 {
            color: #67378d;
            margin-top: 0;
        }

        .subtitulo {
            margin: 25px 0 15px;
            color: #75469a;
            border-bottom: 1px solid #e8dfee;
            padding-bottom: 10px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .campo {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #d7c8e3;
            padding: 12px;
            border-radius: 9px;
            font: inherit;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid #a982c9;
        }

        textarea {
            min-height: 95px;
        }

        .documento,
        .aviso {
            padding: 20px;
            background: #f7f2fb;
            border: 1px solid #e7d9f1;
            border-radius: 12px;
            margin: 15px 0;
        }

        .opciones {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }

        .opciones label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .opciones input {
            width: auto;
        }

        .botones {
            display: flex;
            justify-content: space-between;
            margin-top: 28px;
            gap: 10px;
        }

        button,
        .enlace {
            background: #75409e;
            color: white;
            padding: 12px 20px;
            border: 0;
            border-radius: 9px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        button:hover,
        .enlace:hover {
            background: #5d2c85;
        }

        button.secundario {
            background: #e9e1ef;
            color: #493258;
        }

        button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .error {
            background: #fff0f0;
            border: 1px solid #e8aaaa;
            color: #9c2424;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .oculto {
            display: none !important;
        }

        .resumen {
            background: #f8f4fb;
            border-radius: 12px;
            padding: 20px;
            line-height: 2;
        }

        .nota {
            color: #766b7e;
            font-size: 13px;
            line-height: 1.6;
        }

        .mensaje-archivo {
            font-size: 13px;
            font-weight: bold;
            margin-top: 10px;
        }

        .mensaje-archivo.correcto {
            color: #15803d;
        }

        .mensaje-archivo.incorrecto {
            color: #b91c1c;
        }

        .horarios {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .horario-opcion {
            flex: 1;
            min-width: 145px;
            border: 2px solid #e1d2eb;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            color: #67378d;
        }

        .horario-opcion input {
            width: auto;
            margin-right: 6px;
        }

        .horario-opcion:has(input:checked) {
            border-color: #7844a2;
            background: #f1e7f8;
        }

        @media (max-width: 650px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .tarjeta {
                padding: 18px;
            }

            .horario-opcion {
                min-width: 100%;
            }

            .contenedor {
                margin: 15px auto;
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <header class="cabecera">
        <h1>Nueva solicitud de bautismo</h1>

        <p>
            Completa la información familiar, selecciona la ceremonia
            y adjunta los documentos solicitados.
        </p>
    </header>

    <div class="tarjeta">

        @if($errors->any())
            <div class="error">
                <strong>Corrige los siguientes errores:</strong>

                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pasos">
            @for($i = 0; $i < 6; $i++)
                <div class="indicador {{ $i === 0 ? 'activo' : '' }}"></div>
            @endfor
        </div>

        <div id="numeroPaso" class="numero-paso">
            Paso 1 de 6
        </div>

        <form
            id="formSolicitud"
            action="{{ route('solicitudes.guardar') }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

            <!-- =====================================
                 PASO 1: DATOS DEL MENOR
            ====================================== -->

            <section class="seccion activa">

                <h2>1. Datos del menor</h2>

                <div class="grid">

                    <div class="campo">
                        <label for="menor_nombre">Nombre *</label>

                        <input
                            id="menor_nombre"
                            name="menor[nombre]"
                            required
                            value="{{ old('menor.nombre') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="menor_apellido_paterno">
                            Apellido paterno *
                        </label>

                        <input
                            id="menor_apellido_paterno"
                            name="menor[apellido_paterno]"
                            required
                            value="{{ old('menor.apellido_paterno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="menor_apellido_materno">
                            Apellido materno
                        </label>

                        <input
                            id="menor_apellido_materno"
                            name="menor[apellido_materno]"
                            value="{{ old('menor.apellido_materno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="menor_fecha_nacimiento">
                            Fecha de nacimiento *
                        </label>

                        <input
                            type="date"
                            id="menor_fecha_nacimiento"
                            name="menor[fecha_nacimiento]"
                            max="{{ date('Y-m-d') }}"
                            required
                            value="{{ old('menor.fecha_nacimiento') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="menor_sexo">Sexo *</label>

                        <select
                            id="menor_sexo"
                            name="menor[sexo]"
                            required
                        >
                            <option value="">Seleccionar</option>

                            @foreach(['masculino', 'femenino', 'otro'] as $sexo)
                                <option
                                    value="{{ $sexo }}"
                                    @selected(old('menor.sexo') === $sexo)
                                >
                                    {{ ucfirst($sexo) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">

                        <label for="menor_lugar_selector">
                            Lugar de nacimiento *
                        </label>

                        <select
                            id="menor_lugar_selector"
                            required
                            onchange="actualizarLugarNacimiento()"
                        >
                            <option value="">
                                Selecciona el lugar de nacimiento
                            </option>

                            @foreach($lugaresNacimiento as $lugar)
                                <option
                                    value="{{ $lugar }}"
                                    @selected($lugarAnterior === $lugar)
                                >
                                    {{ $lugar }}
                                </option>
                            @endforeach

                            <option
                                value="otro"
                                @selected($lugarEsOtro)
                            >
                                Otro municipio o estado
                            </option>
                        </select>

                        <div
                            id="contenedorOtroLugar"
                            class="{{ $lugarEsOtro ? '' : 'oculto' }}"
                            style="margin-top:15px"
                        >
                            <label for="menor_otro_lugar">
                                Especifica el municipio y estado *
                            </label>

                            <input
                                type="text"
                                id="menor_otro_lugar"
                                maxlength="150"
                                placeholder="Ejemplo: Morelia, Michoacán"
                                value="{{ $lugarEsOtro ? $lugarAnterior : '' }}"
                            >
                        </div>

                        <input
                            type="hidden"
                            id="menor_lugar_nacimiento"
                            name="menor[lugar_nacimiento]"
                            value="{{ $lugarAnterior }}"
                        >

                    </div>

                </div>

                <h3 class="subtitulo">
                    Documentación del menor
                </h3>

                <div class="documento">

                    <label for="acta_nacimiento">
                        Acta de nacimiento (PDF obligatorio) *
                    </label>

                    <input
                        type="file"
                        id="acta_nacimiento"
                        name="acta_nacimiento"
                        accept=".pdf,application/pdf"
                        required
                    >

                    <p class="nota">
                        Solo se permiten archivos PDF.
                        Tamaño máximo: <strong>20 MB</strong>.
                    </p>

                    <p
                        id="mensajeActaNacimiento"
                        class="mensaje-archivo"
                        aria-live="polite"
                    ></p>

                </div>

            </section>

            <!-- =====================================
                 PASO 2: PADRES O TUTORES
            ====================================== -->

            <section class="seccion">

                <h2>2. Padres o tutores</h2>

                <div class="aviso">
                    Registra los datos del padre, madre o tutor
                    responsable del menor. No es necesario que
                    coincidan con los datos de la cuenta utilizada
                    para realizar la solicitud.
                </div>

                <h3 class="subtitulo">
                    Padre o tutor principal
                </h3>

                <div class="grid">

                    <div class="campo">
                        <label for="padre_nombre">Nombre *</label>

                        <input
                            id="padre_nombre"
                            name="padre[nombre]"
                            required
                            value="{{ old('padre.nombre') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padre_apellido_paterno">
                            Apellido paterno *
                        </label>

                        <input
                            id="padre_apellido_paterno"
                            name="padre[apellido_paterno]"
                            required
                            value="{{ old('padre.apellido_paterno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padre_apellido_materno">
                            Apellido materno
                        </label>

                        <input
                            id="padre_apellido_materno"
                            name="padre[apellido_materno]"
                            value="{{ old('padre.apellido_materno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padre_telefono">
                            Teléfono *
                        </label>

                        <input
                            type="tel"
                            id="padre_telefono"
                            name="padre[telefono]"
                            required
                            value="{{ old('padre.telefono') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padre_parentesco">
                            Parentesco *
                        </label>

                        <select
                            id="padre_parentesco"
                            name="padre[parentesco]"
                            required
                        >
                            <option value="">Seleccionar</option>

                            @foreach(['Padre', 'Madre', 'Tutor legal'] as $tipo)
                                <option
                                    value="{{ $tipo }}"
                                    @selected(old('padre.parentesco') === $tipo)
                                >
                                    {{ $tipo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <h3 class="subtitulo">
                    Madre o segundo tutor (opcional)
                </h3>

                <div class="grid">

                    <div class="campo">
                        <label for="segundo_tutor_nombre">
                            Nombre
                        </label>

                        <input
                            id="segundo_tutor_nombre"
                            name="segundo_tutor[nombre]"
                            value="{{ old('segundo_tutor.nombre') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="segundo_tutor_apellido_paterno">
                            Apellido paterno
                        </label>

                        <input
                            id="segundo_tutor_apellido_paterno"
                            name="segundo_tutor[apellido_paterno]"
                            value="{{ old('segundo_tutor.apellido_paterno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="segundo_tutor_apellido_materno">
                            Apellido materno
                        </label>

                        <input
                            id="segundo_tutor_apellido_materno"
                            name="segundo_tutor[apellido_materno]"
                            value="{{ old('segundo_tutor.apellido_materno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="segundo_tutor_telefono">
                            Teléfono
                        </label>

                        <input
                            type="tel"
                            id="segundo_tutor_telefono"
                            name="segundo_tutor[telefono]"
                            value="{{ old('segundo_tutor.telefono') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="segundo_tutor_parentesco">
                            Parentesco
                        </label>

                        <select
                            id="segundo_tutor_parentesco"
                            name="segundo_tutor[parentesco]"
                        >
                            <option value="">Seleccionar</option>

                            @foreach(['Madre', 'Padre', 'Tutor legal'] as $tipo)
                                <option
                                    value="{{ $tipo }}"
                                    @selected(old('segundo_tutor.parentesco') === $tipo)
                                >
                                    {{ $tipo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </section>

            <!-- =====================================
                 PASO 3: PADRINOS
            ====================================== -->

            <section class="seccion">

                <h2>3. Datos de los padrinos</h2>

                <label>
                    ¿Cómo participarán los padrinos? *
                </label>

                <div class="opciones">

                    <label>
                        <input
                            type="radio"
                            name="tipo_padrinos"
                            value="pareja"
                            required
                            @checked(old('tipo_padrinos', 'pareja') === 'pareja')
                        >
                        Pareja
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="tipo_padrinos"
                            value="unico"
                            required
                            @checked(old('tipo_padrinos') === 'unico')
                        >
                        Único padrino o madrina
                    </label>

                </div>

                <div id="tipoUnico" class="oculto">

                    <div class="campo">
                        <label for="tipo_unico">
                            Seleccionar participación
                        </label>

                        <select
                            id="tipo_unico"
                            name="tipo_unico"
                        >
                            <option
                                value="padrino"
                                @selected(old('tipo_unico', 'padrino') === 'padrino')
                            >
                                Padrino
                            </option>

                            <option
                                value="madrina"
                                @selected(old('tipo_unico') === 'madrina')
                            >
                                Madrina
                            </option>
                        </select>
                    </div>

                </div>

                <h3 class="subtitulo">
                    Primer padrino o madrina
                </h3>

                <div class="grid">

                    <div class="campo">
                        <label for="padrino_nombre">
                            Nombre *
                        </label>

                        <input
                            id="padrino_nombre"
                            name="padrino[nombre]"
                            required
                            value="{{ old('padrino.nombre') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padrino_apellido_paterno">
                            Apellido paterno *
                        </label>

                        <input
                            id="padrino_apellido_paterno"
                            name="padrino[apellido_paterno]"
                            required
                            value="{{ old('padrino.apellido_paterno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padrino_apellido_materno">
                            Apellido materno
                        </label>

                        <input
                            id="padrino_apellido_materno"
                            name="padrino[apellido_materno]"
                            value="{{ old('padrino.apellido_materno') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="padrino_telefono">
                            Teléfono *
                        </label>

                        <input
                            type="tel"
                            id="padrino_telefono"
                            name="padrino[telefono]"
                            required
                            value="{{ old('padrino.telefono') }}"
                        >
                    </div>

                    <div class="campo">
                        <label for="estado_civil_padrino">
                            Estado civil
                        </label>

                        <select
                            id="estado_civil_padrino"
                            name="padrino[estado_civil]"
                        >
                            <option value="">
                                Selecciona un estado civil
                            </option>

                            @foreach($opcionesEstadoCivil as $estado)
                                <option
                                    value="{{ $estado }}"
                                    @selected(old('padrino.estado_civil') === $estado)
                                >
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div id="apartadoPareja">

                    <h3 class="subtitulo">
                        Segunda madrina o padrino
                    </h3>

                    <div class="grid">

                        <div class="campo">
                            <label for="madrina_nombre">
                                Nombre *
                            </label>

                            <input
                                id="madrina_nombre"
                                name="madrina[nombre]"
                                required
                                value="{{ old('madrina.nombre') }}"
                            >
                        </div>

                        <div class="campo">
                            <label for="madrina_apellido_paterno">
                                Apellido paterno *
                            </label>

                            <input
                                id="madrina_apellido_paterno"
                                name="madrina[apellido_paterno]"
                                required
                                value="{{ old('madrina.apellido_paterno') }}"
                            >
                        </div>

                        <div class="campo">
                            <label for="madrina_apellido_materno">
                                Apellido materno
                            </label>

                            <input
                                id="madrina_apellido_materno"
                                name="madrina[apellido_materno]"
                                value="{{ old('madrina.apellido_materno') }}"
                            >
                        </div>

                        <div class="campo">
                            <label for="madrina_telefono">
                                Teléfono *
                            </label>

                            <input
                                type="tel"
                                id="madrina_telefono"
                                name="madrina[telefono]"
                                required
                                value="{{ old('madrina.telefono') }}"
                            >
                        </div>

                        <div class="campo">
                            <label for="estado_civil_madrina">
                                Estado civil
                            </label>

                            <select
                                id="estado_civil_madrina"
                                name="madrina[estado_civil]"
                            >
                                <option value="">
                                    Selecciona un estado civil
                                </option>

                                @foreach($opcionesEstadoCivil as $estado)
                                    <option
                                        value="{{ $estado }}"
                                        @selected(old('madrina.estado_civil') === $estado)
                                    >
                                        {{ $estado }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <h3 class="subtitulo">
                        Documentación de la pareja
                    </h3>

                    <div class="documento">

                        <label for="acta_matrimonio">
                            Acta de matrimonio (PDF obligatorio) *
                        </label>

                        <input
                            type="file"
                            id="acta_matrimonio"
                            name="acta_matrimonio"
                            accept=".pdf,application/pdf"
                            required
                        >

                        <p class="nota">
                            Solo se permiten archivos PDF.
                            Tamaño máximo: <strong>20 MB</strong>.
                        </p>

                        <p
                            id="mensajeActaMatrimonio"
                            class="mensaje-archivo"
                            aria-live="polite"
                        ></p>

                    </div>

                </div>

            </section>

            <!-- =====================================
                 PASO 4: CEREMONIA
            ====================================== -->

            <section class="seccion">

                <h2>4. Fecha y horario del bautismo</h2>

                <div class="aviso">
                    Puedes elegir libremente la fecha de la ceremonia.

                    <p>
                        Los horarios establecidos son:
                        <strong>
                            11:00 a. m., 12:00 p. m. y 1:00 p. m.
                        </strong>
                    </p>

                    <p>
                        Cada horario permite un máximo de dos bautismos.
                    </p>

                    El sacerdote podrá proponer un cambio de fecha
                    u horario que necesitará tu aceptación.
                </div>

                <div class="campo">

                    <label for="fechaCeremonia">
                        Fecha de la ceremonia *
                    </label>

                    <input
                        type="date"
                        id="fechaCeremonia"
                        name="fecha_ceremonia"
                        min="{{ date('Y-m-d') }}"
                        value="{{ old('fecha_ceremonia') }}"
                        required
                    >

                </div>

                <h3 class="subtitulo">
                    Selecciona el horario *
                </h3>

                <div class="horarios">

                    @foreach([
                        '11:00' => '11:00 a. m.',
                        '12:00' => '12:00 p. m.',
                        '13:00' => '1:00 p. m.'
                    ] as $valor => $etiqueta)

                        <label class="horario-opcion">

                            <input
                                type="radio"
                                name="hora_ceremonia"
                                value="{{ $valor }}"
                                required
                                @checked(old('hora_ceremonia') === $valor)
                            >

                            {{ $etiqueta }}

                        </label>

                    @endforeach

                </div>

                <p class="nota">
                    La disponibilidad del horario se verificará
                    al enviar la solicitud.
                </p>

            </section>

            <!-- =====================================
                 PASO 5: PLÁTICA PREBAUTISMAL
            ====================================== -->

            <section class="seccion">

                <h2>5. Plática prebautismal</h2>

                <div class="aviso">
                    La plática es obligatoria para padres y padrinos.

                    <p>
                        Debe realizarse antes de la ceremonia.
                    </p>

                    No existe un límite de cupos para las pláticas.
                </div>

                <div class="campo">

                    <label for="platica">
                        Selecciona la plática *
                    </label>

                    <select
                        id="platica"
                        name="id_platica"
                        required
                    >
                        <option value="">
                            Seleccionar plática
                        </option>

                        @foreach($platicas as $platica)
                            <option
                                value="{{ $platica->id_platica }}"
                                data-fecha="{{ $platica->fecha }}"
                                @selected(old('id_platica') == $platica->id_platica)
                            >
                                {{ $platica->fecha }}
                                | {{ substr($platica->hora_inicio, 0, 5) }}
                                - {{ substr($platica->hora_fin, 0, 5) }}
                                | {{ $platica->lugar ?? 'Parroquia' }}
                            </option>
                        @endforeach
                    </select>

                </div>

                <p id="mensajePlatica" class="nota">
                    Primero selecciona la fecha de la ceremonia.
                </p>

            </section>

            <!-- =====================================
                 PASO 6: CONFIRMACIÓN
            ====================================== -->

            <section class="seccion">

                <h2>6. Confirmación de solicitud</h2>

                <div class="resumen">

                    <p>
                        <strong>Menor:</strong>
                        <span id="resumenMenor">—</span>
                    </p>

                    <p>
                        <strong>Lugar de nacimiento:</strong>
                        <span id="resumenLugar">—</span>
                    </p>

                    <p>
                        <strong>Responsable:</strong>
                        <span id="resumenPadre">—</span>
                    </p>

                    <p>
                        <strong>Padrinos:</strong>
                        <span id="resumenPadrinos">—</span>
                    </p>

                    <p>
                        <strong>Ceremonia:</strong>
                        <span id="resumenCeremonia">—</span>
                    </p>

                    <p>
                        <strong>Plática:</strong>
                        <span id="resumenPlatica">—</span>
                    </p>

                    <p>
                        <strong>Documentos:</strong>
                        <span id="resumenDocumentos">—</span>
                    </p>

                    <p>
                        <strong>Estado inicial:</strong>
                        Pendiente
                    </p>

                </div>

                <div class="campo" style="margin-top:20px">

                    <label for="observaciones">
                        Observaciones adicionales
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                    >{{ old('observaciones') }}</textarea>

                </div>

                <div class="aviso">
                    Al enviar, la solicitud quedará pendiente
                    de revisión y autorización del sacerdote.
                </div>

            </section>

            <!-- =====================================
                 BOTONES DE NAVEGACIÓN
            ====================================== -->

            <div class="botones">

                <button
                    type="button"
                    class="secundario"
                    id="anterior"
                    onclick="cambiarPaso(-1)"
                >
                    Anterior
                </button>

                <button
                    type="button"
                    id="siguiente"
                    onclick="cambiarPaso(1)"
                >
                    Siguiente
                </button>

                <button
                    type="submit"
                    id="enviar"
                    class="oculto"
                >
                    Enviar solicitud
                </button>

            </div>

        </form>

        <p style="margin-top:25px">

            <a
                class="enlace"
                href="{{ route('solicitudes.mis') }}"
            >
                Volver a mis solicitudes
            </a>

        </p>

    </div>

</div>

<script>
    // ==============================================
    // CONFIGURACIÓN GENERAL
    // ==============================================

    let pasoActual = 0;

    const TAMANO_MAXIMO_PDF = 20 * 1024 * 1024;

    const formulario = document.getElementById('formSolicitud');

    const secciones = [
        ...document.querySelectorAll('.seccion')
    ];

    const indicadores = [
        ...document.querySelectorAll('.indicador')
    ];

    const fechaCeremonia = document.getElementById('fechaCeremonia');

    const platica = document.getElementById('platica');

    const actaNacimiento = document.getElementById('acta_nacimiento');

    const actaMatrimonio = document.getElementById('acta_matrimonio');


    // ==============================================
    // OBTENER VALORES
    // ==============================================

    function obtenerValor(nombre) {
        const campo = formulario.elements.namedItem(nombre);

        return campo ? campo.value.trim() : '';
    }

    function obtenerHora() {
        return document.querySelector(
            'input[name="hora_ceremonia"]:checked'
        )?.value || '';
    }

    function obtenerTipoPadrinos() {
        return document.querySelector(
            'input[name="tipo_padrinos"]:checked'
        )?.value || '';
    }


    // ==============================================
    // VALIDACIÓN DE ARCHIVOS PDF - 20 MB
    // ==============================================

    function validarDocumentoPDF(input, mensajeId, obligatorio = true) {

        const mensaje = document.getElementById(mensajeId);

        mensaje.textContent = '';

        mensaje.classList.remove('correcto', 'incorrecto');

        if (input.disabled) {
            return true;
        }

        if (!input.files || input.files.length === 0) {

            if (obligatorio) {
                mensaje.textContent = 'Debes seleccionar un archivo PDF.';
                mensaje.classList.add('incorrecto');
                return false;
            }

            return true;
        }

        const archivo = input.files[0];

        const extensionValida = archivo.name
            .toLowerCase()
            .endsWith('.pdf');

        if (!extensionValida) {

            mensaje.textContent =
                'Error: únicamente se permiten archivos PDF.';

            mensaje.classList.add('incorrecto');

            input.value = '';

            return false;
        }

        if (archivo.size > TAMANO_MAXIMO_PDF) {

            mensaje.textContent =
                'El archivo supera el tamaño máximo permitido de 20 MB.';

            mensaje.classList.add('incorrecto');

            input.value = '';

            return false;
        }

        const tamanoMB = (
            archivo.size / (1024 * 1024)
        ).toFixed(2);

        mensaje.textContent =
            'Archivo PDF válido (' + tamanoMB + ' MB).';

        mensaje.classList.add('correcto');

        return true;
    }


    // ==============================================
    // EVENTOS DE DOCUMENTOS
    // ==============================================

    actaNacimiento.addEventListener('change', function () {

        validarDocumentoPDF(
            this,
            'mensajeActaNacimiento'
        );

    });

    actaMatrimonio.addEventListener('change', function () {

        validarDocumentoPDF(
            this,
            'mensajeActaMatrimonio'
        );

    });


    // ==============================================
    // LUGAR DE NACIMIENTO
    // ==============================================

    function actualizarLugarNacimiento() {

        const selector = document.getElementById(
            'menor_lugar_selector'
        );

        const contenedor = document.getElementById(
            'contenedorOtroLugar'
        );

        const campoOtro = document.getElementById(
            'menor_otro_lugar'
        );

        const campoFinal = document.getElementById(
            'menor_lugar_nacimiento'
        );

        const esOtro = selector.value === 'otro';

        contenedor.classList.toggle('oculto', !esOtro);

        campoOtro.required = esOtro;

        campoOtro.disabled = !esOtro;

        if (esOtro) {
            campoFinal.value = campoOtro.value.trim();
        } else {
            campoFinal.value = selector.value;
        }
    }

    document.getElementById('menor_otro_lugar')
        .addEventListener('input', actualizarLugarNacimiento);


    // ==============================================
    // MOSTRAR CAMPOS SEGÚN TIPO DE PADRINOS
    // ==============================================

    function actualizarPadrinos() {

        const pareja = obtenerTipoPadrinos() === 'pareja';

        const apartadoPareja = document.getElementById(
            'apartadoPareja'
        );

        const tipoUnico = document.getElementById(
            'tipoUnico'
        );

        apartadoPareja.classList.toggle('oculto', !pareja);

        tipoUnico.classList.toggle('oculto', pareja);

        apartadoPareja
            .querySelectorAll('input, select, textarea')
            .forEach(campo => {
                campo.disabled = !pareja;
            });

        tipoUnico
            .querySelectorAll('input, select, textarea')
            .forEach(campo => {
                campo.disabled = pareja;
            });

        // El acta de matrimonio solo es obligatoria
        // cuando se selecciona una pareja.

        actaMatrimonio.required = pareja;

        if (!pareja) {
            document.getElementById(
                'mensajeActaMatrimonio'
            ).textContent = '';
        }
    }


    // ==============================================
    // VALIDAR FECHAS DE PLÁTICAS
    // ==============================================

    function actualizarPlaticas() {

        const fecha = fechaCeremonia.value;

        [...platica.options].forEach(opcion => {

            if (!opcion.value) {
                return;
            }

            const fechaPlatica = opcion.dataset.fecha;

            opcion.disabled = !fecha || fechaPlatica >= fecha;
        });

        if (platica.selectedOptions[0]?.disabled) {
            platica.value = '';
        }

        const mensaje = document.getElementById(
            'mensajePlatica'
        );

        mensaje.textContent = fecha
            ? 'Solo puedes seleccionar pláticas anteriores al ' + fecha + '.'
            : 'Primero selecciona la fecha de la ceremonia.';
    }


    // ==============================================
    // VALIDAR CADA PASO
    // ==============================================

    function validarPaso(indice = pasoActual) {

        const seccion = secciones[indice];

        const campos = seccion.querySelectorAll(
            'input, select, textarea'
        );

        for (const campo of campos) {

            if (
                campo.disabled ||
                campo.closest('.oculto')
            ) {
                continue;
            }

            if (!campo.checkValidity()) {

                if (pasoActual !== indice) {
                    pasoActual = indice;
                    mostrarPaso();
                }

                campo.reportValidity();

                return false;
            }
        }

        // PASO 1: MENOR Y ACTA DE NACIMIENTO

        if (indice === 0) {

            actualizarLugarNacimiento();

            const lugar = obtenerValor(
                'menor[lugar_nacimiento]'
            );

            if (!lugar) {

                alert(
                    'Selecciona o especifica el lugar de nacimiento.'
                );

                return false;
            }

            if (!validarDocumentoPDF(
                actaNacimiento,
                'mensajeActaNacimiento'
            )) {

                alert(
                    'Verifica el acta de nacimiento. Debe ser PDF y pesar máximo 20 MB.'
                );

                return false;
            }
        }

        // PASO 3: PADRINOS Y ACTA DE MATRIMONIO

        if (indice === 2) {

            const tipo = obtenerTipoPadrinos();

            if (!tipo) {

                alert(
                    'Selecciona si participará una pareja o un único padrino o madrina.'
                );

                return false;
            }

            if (tipo === 'pareja') {

                if (!validarDocumentoPDF(
                    actaMatrimonio,
                    'mensajeActaMatrimonio'
                )) {

                    alert(
                        'Verifica el acta de matrimonio. Debe ser PDF y pesar máximo 20 MB.'
                    );

                    return false;
                }
            }
        }

        // PASO 4: CEREMONIA

        if (indice === 3) {

            if (!fechaCeremonia.value || !obtenerHora()) {

                alert(
                    'Selecciona la fecha y el horario de la ceremonia.'
                );

                return false;
            }
        }

        // PASO 5: PLÁTICA

        if (indice === 4) {

            if (
                !platica.value ||
                platica.selectedOptions[0]?.disabled
            ) {

                alert(
                    'Selecciona una plática anterior a la ceremonia.'
                );

                return false;
            }
        }

        return true;
    }


    // ==============================================
    // RESUMEN
    // ==============================================

    function actualizarResumen() {

        actualizarLugarNacimiento();

        const nombreMenor = [
            obtenerValor('menor[nombre]'),
            obtenerValor('menor[apellido_paterno]'),
            obtenerValor('menor[apellido_materno]')
        ].filter(Boolean).join(' ');

        const nombrePadre = [
            obtenerValor('padre[nombre]'),
            obtenerValor('padre[apellido_paterno]'),
            obtenerValor('padre[apellido_materno]')
        ].filter(Boolean).join(' ');

        const nombrePadrino = [
            obtenerValor('padrino[nombre]'),
            obtenerValor('padrino[apellido_paterno]'),
            obtenerValor('padrino[apellido_materno]')
        ].filter(Boolean).join(' ');

        const nombreMadrina = [
            obtenerValor('madrina[nombre]'),
            obtenerValor('madrina[apellido_paterno]'),
            obtenerValor('madrina[apellido_materno]')
        ].filter(Boolean).join(' ');

        const pareja = obtenerTipoPadrinos() === 'pareja';

        document.getElementById('resumenMenor').textContent =
            nombreMenor || '—';

        document.getElementById('resumenLugar').textContent =
            obtenerValor('menor[lugar_nacimiento]') || '—';

        document.getElementById('resumenPadre').textContent =
            nombrePadre || '—';

        document.getElementById('resumenPadrinos').textContent =
            pareja
                ? nombrePadrino + ' y ' + nombreMadrina
                : nombrePadrino;

        const etiquetasHoras = {
            '11:00': '11:00 a. m.',
            '12:00': '12:00 p. m.',
            '13:00': '1:00 p. m.'
        };

        document.getElementById(
            'resumenCeremonia'
        ).textContent = fechaCeremonia.value
            ? fechaCeremonia.value + ' | ' +
              (etiquetasHoras[obtenerHora()] || 'Sin horario')
            : '—';

        document.getElementById(
            'resumenPlatica'
        ).textContent = platica.value
            ? platica.selectedOptions[0].textContent.trim()
            : '—';

        document.getElementById(
            'resumenDocumentos'
        ).textContent = pareja
            ? 'Acta de nacimiento y acta de matrimonio'
            : 'Acta de nacimiento';
    }


    // ==============================================
    // MOSTRAR PASO ACTUAL
    // ==============================================

    function mostrarPaso() {

        secciones.forEach((seccion, i) => {

            seccion.classList.toggle(
                'activa',
                i === pasoActual
            );
        });

        indicadores.forEach((indicador, i) => {

            indicador.classList.toggle(
                'activo',
                i <= pasoActual
            );
        });

        document.getElementById(
            'numeroPaso'
        ).textContent = 'Paso ' + (pasoActual + 1) + ' de 6';

        document.getElementById(
            'anterior'
        ).disabled = pasoActual === 0;

        document.getElementById(
            'siguiente'
        ).classList.toggle(
            'oculto',
            pasoActual === 5
        );

        document.getElementById(
            'enviar'
        ).classList.toggle(
            'oculto',
            pasoActual !== 5
        );

        if (pasoActual === 5) {
            actualizarResumen();
        }

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }


    // ==============================================
    // AVANZAR O RETROCEDER
    // ==============================================

    function cambiarPaso(direccion) {

        if (direccion > 0 && !validarPaso()) {
            return;
        }

        pasoActual = Math.max(
            0,
            Math.min(5, pasoActual + direccion)
        );

        mostrarPaso();
    }


    // ==============================================
    // EVENTOS
    // ==============================================

    document.querySelectorAll(
        'input[name="tipo_padrinos"]'
    ).forEach(radio => {

        radio.addEventListener(
            'change',
            actualizarPadrinos
        );
    });

    fechaCeremonia.addEventListener(
        'change',
        actualizarPlaticas
    );


    // ==============================================
    // ENVÍO DEL FORMULARIO
    // ==============================================

    formulario.addEventListener('submit', function(event) {

        actualizarLugarNacimiento();
        actualizarPlaticas();

        // Validar todas las secciones.

        for (let i = 0; i < 5; i++) {

            pasoActual = i;
            mostrarPaso();

            if (!validarPaso(i)) {

                event.preventDefault();

                return;
            }
        }

        pasoActual = 5;
        mostrarPaso();

        // Evitar múltiples envíos.

        document.getElementById('enviar').disabled = true;
    });


    // ==============================================
    // INICIALIZAR
    // ==============================================

    actualizarLugarNacimiento();
    actualizarPadrinos();
    actualizarPlaticas();
    mostrarPaso();

</script>

</body>
</html>