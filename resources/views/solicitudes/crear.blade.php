
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nueva solicitud de bautismo</title>

    <style>
        * { box-sizing: border-box; }

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

        .cabecera h1 { margin: 0 0 10px; }
        .cabecera p { margin: 0; opacity: .9; }

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

        .indicador.activo { background: #7844a2; }

        .numero-paso {
            color: #75469a;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .seccion { display: none; }
        .seccion.activa { display: block; }

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

        .campo { margin-bottom: 16px; }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid #d7c8e3;
            padding: 12px;
            border-radius: 9px;
            font: inherit;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: 2px solid #a982c9;
        }

        textarea { min-height: 95px; }

        .documento, .aviso {
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

        .opciones input { width: auto; }

        .botones {
            display: flex;
            justify-content: space-between;
            margin-top: 28px;
            gap: 10px;
        }

        button, .enlace {
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

        button:hover { background: #5d2c85; }

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

        .oculto { display: none !important; }

        .resumen {
            background: #f8f4fb;
            border-radius: 12px;
            padding: 20px;
            line-height: 2;
        }

        .nota {
            color: #766b7e;
            font-size: 13px;
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

        @media(max-width: 650px) {
            .grid { grid-template-columns: 1fr; }
            .tarjeta { padding: 18px; }
            .horario-opcion { min-width: 100%; }
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
        >
            @csrf

            <!-- PASO 1: MENOR -->
            <section class="seccion activa">
                <h2>1. Datos del menor</h2>

                <div class="grid">
                    <div class="campo">
                        <label>Nombre *</label>
                        <input name="menor[nombre]" required
                               value="{{ old('menor.nombre') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido paterno *</label>
                        <input name="menor[apellido_paterno]" required
                               value="{{ old('menor.apellido_paterno') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido materno</label>
                        <input name="menor[apellido_materno]"
                               value="{{ old('menor.apellido_materno') }}">
                    </div>

                    <div class="campo">
                        <label>Fecha de nacimiento *</label>
                        <input type="date"
                               name="menor[fecha_nacimiento]"
                               max="{{ date('Y-m-d') }}"
                               required
                               value="{{ old('menor.fecha_nacimiento') }}">
                    </div>

                    <div class="campo">
                        <label>Sexo *</label>
                        <select name="menor[sexo]" required>
                            <option value="">Seleccionar</option>
                            @foreach(['masculino','femenino','otro'] as $sexo)
                                <option value="{{ $sexo }}"
                                    @selected(old('menor.sexo') === $sexo)>
                                    {{ ucfirst($sexo) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">
                        <label>Lugar de nacimiento *</label>
                        <input name="menor[lugar_nacimiento]" required
                               value="{{ old('menor.lugar_nacimiento') }}">
                    </div>
                </div>

                <h3 class="subtitulo">Documentación del menor</h3>

                <div class="documento">
                    <label>Acta de nacimiento (PDF obligatorio) *</label>
                    <input type="file"
                           name="acta_nacimiento"
                           accept=".pdf,application/pdf"
                           required>
                    <p class="nota">Tamaño máximo: 5 MB.</p>
                </div>
            </section>

            <!-- PASO 2: PADRES -->
            <section class="seccion">
                <h2>2. Padres o tutores</h2>

                <div class="aviso">
                    El padre o tutor principal debe ser la persona
                    responsable de esta cuenta.
                </div>

                <h3 class="subtitulo">Padre o tutor principal</h3>

                <div class="grid">
                    <div class="campo">
                        <label>Nombre *</label>
                        <input name="padre[nombre]" required
                               value="{{ old('padre.nombre') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido paterno *</label>
                        <input name="padre[apellido_paterno]" required
                               value="{{ old('padre.apellido_paterno') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido materno</label>
                        <input name="padre[apellido_materno]"
                               value="{{ old('padre.apellido_materno') }}">
                    </div>

                    <div class="campo">
                        <label>Teléfono *</label>
                        <input type="tel" name="padre[telefono]" required
                               value="{{ old('padre.telefono') }}">
                    </div>

                    <div class="campo">
                        <label>Parentesco *</label>
                        <select name="padre[parentesco]" required>
                            <option value="">Seleccionar</option>
                            @foreach(['Padre','Madre','Tutor legal'] as $tipo)
                                <option value="{{ $tipo }}"
                                    @selected(old('padre.parentesco') === $tipo)>
                                    {{ $tipo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h3 class="subtitulo">Madre o segundo tutor (opcional)</h3>

                <div class="grid">
                    <div class="campo">
                        <label>Nombre</label>
                        <input name="segundo_tutor[nombre]"
                               value="{{ old('segundo_tutor.nombre') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido paterno</label>
                        <input name="segundo_tutor[apellido_paterno]"
                               value="{{ old('segundo_tutor.apellido_paterno') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido materno</label>
                        <input name="segundo_tutor[apellido_materno]"
                               value="{{ old('segundo_tutor.apellido_materno') }}">
                    </div>

                    <div class="campo">
                        <label>Teléfono</label>
                        <input type="tel" name="segundo_tutor[telefono]"
                               value="{{ old('segundo_tutor.telefono') }}">
                    </div>

                    <div class="campo">
                        <label>Parentesco</label>
                        <select name="segundo_tutor[parentesco]">
                            <option value="">Seleccionar</option>
                            @foreach(['Madre','Padre','Tutor legal'] as $tipo)
                                <option value="{{ $tipo }}"
                                    @selected(old('segundo_tutor.parentesco') === $tipo)>
                                    {{ $tipo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <!-- PASO 3: PADRINOS -->
            <section class="seccion">
                <h2>3. Datos de los padrinos</h2>

                <label>¿Cómo participarán los padrinos? *</label>

                <div class="opciones">
                    <label>
                        <input type="radio"
                               name="tipo_padrinos"
                               value="pareja"
                               @checked(old('tipo_padrinos','pareja') === 'pareja')>
                        Pareja
                    </label>

                    <label>
                        <input type="radio"
                               name="tipo_padrinos"
                               value="unico"
                               @checked(old('tipo_padrinos') === 'unico')>
                        Único padrino o madrina
                    </label>
                </div>

                <div id="tipoUnico" class="oculto">
                    <label>Seleccionar participación</label>
                    <select name="tipo_unico">
                        <option value="padrino"
                            @selected(old('tipo_unico') === 'padrino')>
                            Padrino
                        </option>
                        <option value="madrina"
                            @selected(old('tipo_unico') === 'madrina')>
                            Madrina
                        </option>
                    </select>
                </div>

                <h3 class="subtitulo">Primer padrino o madrina</h3>

                <div class="grid">
                    <div class="campo">
                        <label>Nombre *</label>
                        <input name="padrino[nombre]" required
                               value="{{ old('padrino.nombre') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido paterno *</label>
                        <input name="padrino[apellido_paterno]" required
                               value="{{ old('padrino.apellido_paterno') }}">
                    </div>

                    <div class="campo">
                        <label>Apellido materno</label>
                        <input name="padrino[apellido_materno]"
                               value="{{ old('padrino.apellido_materno') }}">
                    </div>

                    <div class="campo">
                        <label>Teléfono *</label>
                        <input type="tel" name="padrino[telefono]" required
                               value="{{ old('padrino.telefono') }}">
                    </div>

                    <div class="campo">
                        <label>Estado civil</label>
                        <input name="padrino[estado_civil]"
                               value="{{ old('padrino.estado_civil') }}">
                    </div>
                </div>

                <div id="apartadoPareja">
                    <h3 class="subtitulo">Segunda madrina o padrino</h3>

                    <div class="grid">
                        <div class="campo">
                            <label>Nombre *</label>
                            <input name="madrina[nombre]" required
                                   value="{{ old('madrina.nombre') }}">
                        </div>

                        <div class="campo">
                            <label>Apellido paterno *</label>
                            <input name="madrina[apellido_paterno]" required
                                   value="{{ old('madrina.apellido_paterno') }}">
                        </div>

                        <div class="campo">
                            <label>Apellido materno</label>
                            <input name="madrina[apellido_materno]"
                                   value="{{ old('madrina.apellido_materno') }}">
                        </div>

                        <div class="campo">
                            <label>Teléfono *</label>
                            <input type="tel" name="madrina[telefono]" required
                                   value="{{ old('madrina.telefono') }}">
                        </div>

                        <div class="campo">
                            <label>Estado civil</label>
                            <input name="madrina[estado_civil]"
                                   value="{{ old('madrina.estado_civil') }}">
                        </div>
                    </div>

                    <h3 class="subtitulo">Documentación de la pareja</h3>

                    <div class="documento">
                        <label>Acta de matrimonio (PDF obligatorio) *</label>
                        <input type="file"
                               name="acta_matrimonio"
                               accept=".pdf,application/pdf"
                               required>
                        <p class="nota">Tamaño máximo: 5 MB.</p>
                    </div>
                </div>
            </section>

            <!-- PASO 4: CEREMONIA -->
            <section class="seccion">
                <h2>4. Fecha y horario del bautismo</h2>

                <div class="aviso">
                    Puedes elegir libremente la fecha de la ceremonia.
                    Los horarios establecidos son 11:00 a. m.,
                    12:00 p. m. y 1:00 p. m.
                    El sacerdote podrá proponer un cambio que
                    necesitará tu aceptación.
                </div>

                <div class="campo">
                    <label>Fecha de la ceremonia *</label>
                    <input
                        type="date"
                        id="fechaCeremonia"
                        name="fecha_ceremonia"
                        min="{{ date('Y-m-d') }}"
                        value="{{ old('fecha_ceremonia') }}"
                        required
                    >
                </div>

                <h3 class="subtitulo">Selecciona el horario *</h3>

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
                    La fecha y el horario se verificarán al enviar
                    la solicitud.
                </p>
            </section>

            <!-- PASO 5: PLÁTICA -->
            <section class="seccion">
                <h2>5. Plática prebautismal</h2>

                <div class="aviso">
                    La plática es obligatoria para padres y padrinos.
                    Debe realizarse antes de la ceremonia.
                    No existe un límite de cupos.
                </div>

                <div class="campo">
                    <label>Selecciona la plática *</label>

                    <select id="platica" name="id_platica" required>
                        <option value="">Seleccionar plática</option>

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

            <!-- PASO 6: CONFIRMACIÓN -->
            <section class="seccion">
                <h2>6. Confirmación de solicitud</h2>

                <div class="resumen">
                    <p>
                        <strong>Menor:</strong>
                        <span id="resumenMenor">—</span>
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
                    <label>Observaciones adicionales</label>
                    <textarea name="observaciones">{{ old('observaciones') }}</textarea>
                </div>

                <div class="aviso">
                    Al enviar, la solicitud quedará pendiente
                    de revisión y autorización del sacerdote.
                </div>
            </section>

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
            <a class="enlace" href="{{ route('solicitudes.mis') }}">
                Volver a mis solicitudes
            </a>
        </p>
    </div>
</div>

<script>
    let pasoActual = 0;

    const formulario = document.getElementById('formSolicitud');
    const secciones = [...document.querySelectorAll('.seccion')];
    const indicadores = [...document.querySelectorAll('.indicador')];

    const fechaCeremonia = document.getElementById('fechaCeremonia');
    const platica = document.getElementById('platica');

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

    function actualizarPadrinos() {
        const pareja = obtenerTipoPadrinos() === 'pareja';

        document.getElementById('apartadoPareja')
            .classList.toggle('oculto', !pareja);

        document.getElementById('tipoUnico')
            .classList.toggle('oculto', pareja);

        document.querySelectorAll('#apartadoPareja input')
            .forEach(input => {
                input.disabled = !pareja;
            });

        document.querySelectorAll('#tipoUnico select')
            .forEach(select => {
                select.disabled = pareja;
            });
    }

    function actualizarPlaticas() {
        const fecha = fechaCeremonia.value;

        [...platica.options].forEach(opcion => {
            if (!opcion.value) return;

            const fechaPlatica = opcion.dataset.fecha;

            opcion.disabled = !fecha || fechaPlatica >= fecha;
        });

        if (platica.selectedOptions[0]?.disabled) {
            platica.value = '';
        }

        const mensaje = document.getElementById('mensajePlatica');

        mensaje.textContent = fecha
            ? 'Solo puedes seleccionar pláticas anteriores al ' + fecha + '.'
            : 'Primero selecciona la fecha de la ceremonia.';
    }

    function validarPaso() {
        const seccion = secciones[pasoActual];

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
                campo.reportValidity();
                return false;
            }
        }

        if (pasoActual === 3) {
            if (!fechaCeremonia.value || !obtenerHora()) {
                alert('Selecciona la fecha y el horario de la ceremonia.');
                return false;
            }
        }

        if (pasoActual === 4) {
            if (!platica.value || platica.selectedOptions[0]?.disabled) {
                alert(
                    'Selecciona una plática anterior a la ceremonia.'
                );
                return false;
            }
        }

        return true;
    }

    function actualizarResumen() {
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
            obtenerValor('padrino[apellido_paterno]')
        ].filter(Boolean).join(' ');

        const nombreMadrina = [
            obtenerValor('madrina[nombre]'),
            obtenerValor('madrina[apellido_paterno]')
        ].filter(Boolean).join(' ');

        const pareja = obtenerTipoPadrinos() === 'pareja';

        document.getElementById('resumenMenor').textContent =
            nombreMenor || '—';

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

        document.getElementById('resumenCeremonia').textContent =
            fechaCeremonia.value
                ? fechaCeremonia.value + ' | ' +
                  (etiquetasHoras[obtenerHora()] || 'Sin horario')
                : '—';

        document.getElementById('resumenPlatica').textContent =
            platica.value
                ? platica.selectedOptions[0].textContent.trim()
                : '—';

        document.getElementById('resumenDocumentos').textContent =
            pareja
                ? 'Acta de nacimiento y acta de matrimonio'
                : 'Acta de nacimiento';
    }

    function mostrarPaso() {
        secciones.forEach((seccion, i) => {
            seccion.classList.toggle('activa', i === pasoActual);
        });

        indicadores.forEach((indicador, i) => {
            indicador.classList.toggle('activo', i <= pasoActual);
        });

        document.getElementById('numeroPaso').textContent =
            'Paso ' + (pasoActual + 1) + ' de 6';

        document.getElementById('anterior').disabled =
            pasoActual === 0;

        document.getElementById('siguiente')
            .classList.toggle('oculto', pasoActual === 5);

        document.getElementById('enviar')
            .classList.toggle('oculto', pasoActual !== 5);

        if (pasoActual === 5) {
            actualizarResumen();
        }

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

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

    document.querySelectorAll(
        'input[name="tipo_padrinos"]'
    ).forEach(radio => {
        radio.addEventListener('change', actualizarPadrinos);
    });

    fechaCeremonia.addEventListener(
        'change',
        actualizarPlaticas
    );

    formulario.addEventListener('submit', function(event) {
        actualizarPlaticas();

        if (!fechaCeremonia.value || !obtenerHora()) {
            event.preventDefault();
            alert('Debes seleccionar la fecha y el horario.');
            pasoActual = 3;
            mostrarPaso();
            return;
        }

        if (!platica.value) {
            event.preventDefault();
            alert('Debes seleccionar una plática válida.');
            pasoActual = 4;
            mostrarPaso();
            return;
        }

        // Los campos de otras secciones están ocultos.
        // Validamos todas las secciones antes de enviar.
        for (let i = 0; i < 5; i++) {
            pasoActual = i;

            if (!validarPaso()) {
                event.preventDefault();
                mostrarPaso();
                return;
            }
        }

        pasoActual = 5;
        mostrarPaso();

        document.getElementById('enviar').disabled = true;
    });

    actualizarPadrinos();
    actualizarPlaticas();
    mostrarPaso();
</script>

</body>
</html>
