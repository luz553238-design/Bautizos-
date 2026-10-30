
@extends('layouts.app')

@section('title', $esEdicion ? 'Editar iglesia' : 'Registrar iglesia')

@section('content')

@php
    $direccion = $iglesia->direccion;

    $estadosMunicipios = [
        'Estado de México' => [
            'Valle de Bravo',
            'Amanalco',
            'Donato Guerra',
            'Ixtapan del Oro',
            'Santo Tomás',
            'Temascaltepec',
            'Zacazonapan',
            'Toluca',
            'Metepec',
            'Zinacantepec',
            'Almoloya de Juárez',
            'Villa Victoria',
            'Villa de Allende',
            'Atlacomulco',
            'Ixtlahuaca',
            'Jilotepec',
            'Tenancingo',
            'Malinalco',
            'Tenango del Valle',
            'Lerma',
            'Ocoyoacac',
            'Naucalpan de Juárez',
            'Tlalnepantla de Baz',
            'Ecatepec de Morelos',
            'Nezahualcóyotl',
            'Texcoco',
            'Chalco',
            'Cuautitlán Izcalli',
            'Huixquilucan',
            'Atizapán de Zaragoza',
        ],
        'Ciudad de México' => [
            'Álvaro Obregón',
            'Azcapotzalco',
            'Benito Juárez',
            'Coyoacán',
            'Cuajimalpa de Morelos',
            'Cuauhtémoc',
            'Gustavo A. Madero',
            'Iztacalco',
            'Iztapalapa',
            'La Magdalena Contreras',
            'Miguel Hidalgo',
            'Milpa Alta',
            'Tláhuac',
            'Tlalpan',
            'Venustiano Carranza',
            'Xochimilco',
        ],
        'Michoacán' => [
            'Morelia',
            'Zitácuaro',
            'Tuxpan',
            'Maravatío',
            'Ciudad Hidalgo',
            'Uruapan',
            'Pátzcuaro',
            'Zamora',
            'La Piedad',
            'Lázaro Cárdenas',
        ],
        'Guerrero' => [
            'Acapulco de Juárez',
            'Chilpancingo de los Bravo',
            'Iguala de la Independencia',
            'Taxco de Alarcón',
            'Zihuatanejo de Azueta',
        ],
        'Morelos' => [
            'Cuernavaca',
            'Cuautla',
            'Jiutepec',
            'Temixco',
            'Yautepec',
            'Jojutla',
            'Tepoztlán',
        ],
        'Puebla' => [
            'Puebla',
            'Atlixco',
            'Cholula',
            'Tehuacán',
            'Huauchinango',
            'Zacatlán',
            'Teziutlán',
        ],
        'Querétaro' => [
            'Querétaro',
            'Corregidora',
            'El Marqués',
            'San Juan del Río',
            'Tequisquiapan',
            'Cadereyta de Montes',
        ],
        'Hidalgo' => [
            'Pachuca de Soto',
            'Mineral de la Reforma',
            'Tulancingo de Bravo',
            'Tizayuca',
            'Tula de Allende',
            'Actopan',
        ],
    ];

    $estadoSeleccionado = old(
        'estado',
        $direccion?->estado ?? 'Estado de México'
    );

    $municipioSeleccionado = old(
        'municipio',
        $direccion?->municipio ?? ''
    );

    // Conservar valores anteriores aunque no estén en el catálogo inicial.
    if (
        $estadoSeleccionado &&
        !array_key_exists($estadoSeleccionado, $estadosMunicipios)
    ) {
        $estadosMunicipios[$estadoSeleccionado] = [];
    }

    if (
        $municipioSeleccionado &&
        !in_array(
            $municipioSeleccionado,
            $estadosMunicipios[$estadoSeleccionado] ?? [],
            true
        )
    ) {
        $estadosMunicipios[$estadoSeleccionado][] = $municipioSeleccionado;
    }

    foreach ($estadosMunicipios as &$municipios) {
        $municipios = array_values(array_unique($municipios));
        sort($municipios, SORT_NATURAL);
    }

    unset($municipios);

    ksort($estadosMunicipios, SORT_NATURAL);
@endphp

<style>
    .iglesia-formulario {
        max-width: 1200px;
        margin: 0 auto;
    }

    .iglesia-encabezado {
        margin-bottom: 24px;
    }

    .iglesia-encabezado h1 {
        font-size: 28px;
        font-weight: 800;
        color: #34263f;
        margin-bottom: 8px;
    }

    .iglesia-encabezado p {
        color: #777589;
        margin: 0;
    }

    .iglesia-tarjeta {
        background: #ffffff;
        border: 1px solid #e9e5ee;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 25px;
    }

    .iglesia-seccion {
        padding: 24px 30px;
        border-bottom: 1px solid #eee9f1;
    }

    .iglesia-seccion:last-child {
        border-bottom: none;
    }

    .iglesia-seccion h2 {
        font-size: 18px;
        font-weight: 800;
        color: #694b76;
        margin-bottom: 20px;
    }

    .iglesia-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px 25px;
    }

    .iglesia-campo {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .iglesia-campo.completo {
        grid-column: 1 / -1;
    }

    .iglesia-campo label {
        font-weight: 700;
        color: #34263f;
    }

    .iglesia-campo input,
    .iglesia-campo select,
    .iglesia-campo textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ddd7e5;
        border-radius: 10px;
        background: #fff;
        color: #252238;
        font-family: inherit;
        font-size: 14px;
    }

    .iglesia-campo select {
        cursor: pointer;
        appearance: auto;
    }

    .iglesia-campo input:focus,
    .iglesia-campo select:focus,
    .iglesia-campo textarea:focus {
        outline: none;
        border-color: #694b76;
        box-shadow: 0 0 0 3px rgba(105,75,118,.1);
    }

    .iglesia-obligatorio {
        color: #d44866;
    }

    .iglesia-error {
        color: #b42339;
        font-size: 12px;
    }

    .iglesia-ayuda {
        color: #777589;
        font-size: 12px;
    }

    .iglesia-acciones {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        padding: 22px 30px;
        background: #faf8fc;
    }

    @media(max-width: 700px) {
        .iglesia-grid {
            grid-template-columns: 1fr;
        }

        .iglesia-seccion {
            padding: 20px;
        }

        .iglesia-acciones {
            padding: 20px;
        }
    }
</style>

<div class="iglesia-formulario">

    <div class="iglesia-encabezado">
        <h1>
            <i class="bi bi-buildings"></i>
            {{ $esEdicion ? 'Editar iglesia' : 'Registrar nueva iglesia' }}
        </h1>

        <p>
            Registra los datos generales y la ubicación
            de la iglesia, parroquia o capilla.
        </p>
    </div>

    <form
        method="POST"
        action="{{ $esEdicion
            ? route('iglesias.update', $iglesia)
            : route('iglesias.store') }}"
    >
        @csrf

        @if($esEdicion)
            @method('PUT')
        @endif

        <div class="iglesia-tarjeta">

            <!-- INFORMACIÓN DE LA IGLESIA -->

            <div class="iglesia-seccion">

                <h2>
                    <i class="bi bi-buildings"></i>
                    Información de la iglesia
                </h2>

                <div class="iglesia-grid">

                    <div class="iglesia-campo completo">
                        <label for="nombre">
                            Nombre de la iglesia
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <input
                            id="nombre"
                            type="text"
                            name="nombre"
                            maxlength="180"
                            value="{{ old('nombre', $iglesia->nombre) }}"
                            placeholder="Ej. Parroquia de San Francisco de Asís"
                            required
                        >

                        @error('nombre')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="tipo">
                            Tipo de iglesia
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <select id="tipo" name="tipo" required>
                            @foreach([
                                'Parroquia',
                                'Capilla',
                                'Santuario',
                                'Basílica',
                                'Catedral',
                                'Otra'
                            ] as $tipo)
                                <option
                                    value="{{ $tipo }}"
                                    @selected(old('tipo', $iglesia->tipo ?? 'Parroquia') === $tipo)
                                >
                                    {{ $tipo }}
                                </option>
                            @endforeach
                        </select>

                        @error('tipo')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="activo">Estado de actividad</label>

                        <select id="activo" name="activo" required>
                            <option
                                value="1"
                                @selected((string) old('activo', (int) ($iglesia->activo ?? true)) === '1')
                            >
                                Activa
                            </option>

                            <option
                                value="0"
                                @selected((string) old('activo', (int) ($iglesia->activo ?? true)) === '0')
                            >
                                Inactiva
                            </option>
                        </select>
                    </div>

                    <div class="iglesia-campo">
                        <label for="telefono">Teléfono</label>

                        <input
                            id="telefono"
                            type="text"
                            name="telefono"
                            maxlength="20"
                            value="{{ old('telefono', $iglesia->telefono) }}"
                            placeholder="Teléfono de contacto"
                        >

                        @error('telefono')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="correo">Correo electrónico</label>

                        <input
                            id="correo"
                            type="email"
                            name="correo"
                            maxlength="150"
                            value="{{ old('correo', $iglesia->correo) }}"
                            placeholder="contacto@iglesia.com"
                        >

                        @error('correo')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>
            </div>

            <!-- DIRECCIÓN DE LA IGLESIA -->

            <div class="iglesia-seccion">

                <h2>
                    <i class="bi bi-geo-alt"></i>
                    Dirección de la iglesia
                </h2>

                <div class="iglesia-grid">

                    <div class="iglesia-campo completo">
                        <label for="calle">
                            Calle
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <input
                            id="calle"
                            type="text"
                            name="calle"
                            maxlength="180"
                            value="{{ old('calle', $direccion?->calle) }}"
                            required
                        >

                        @error('calle')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="numero_exterior">
                            Número exterior
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <input
                            id="numero_exterior"
                            type="text"
                            name="numero_exterior"
                            maxlength="20"
                            value="{{ old('numero_exterior', $direccion?->numero_exterior) }}"
                            required
                        >

                        @error('numero_exterior')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="numero_interior">Número interior</label>

                        <input
                            id="numero_interior"
                            type="text"
                            name="numero_interior"
                            maxlength="20"
                            value="{{ old('numero_interior', $direccion?->numero_interior) }}"
                        >

                        @error('numero_interior')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="colonia">
                            Colonia o localidad
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <input
                            id="colonia"
                            type="text"
                            name="colonia"
                            maxlength="150"
                            value="{{ old('colonia', $direccion?->colonia) }}"
                            required
                        >

                        @error('colonia')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo">
                        <label for="codigo_postal">
                            Código postal
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <input
                            id="codigo_postal"
                            type="text"
                            name="codigo_postal"
                            maxlength="5"
                            inputmode="numeric"
                            pattern="[0-9]{5}"
                            value="{{ old('codigo_postal', $direccion?->codigo_postal) }}"
                            required
                        >

                        @error('codigo_postal')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- COMBO DE ESTADOS -->

                    <div class="iglesia-campo">
                        <label for="estado">
                            Estado
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            required
                        >
                            <option value="">
                                Selecciona un estado
                            </option>

                            @foreach(array_keys($estadosMunicipios) as $estado)
                                <option
                                    value="{{ $estado }}"
                                    @selected($estadoSeleccionado === $estado)
                                >
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>

                        @error('estado')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- COMBO DE MUNICIPIOS -->

                    <div class="iglesia-campo">
                        <label for="municipio">
                            Municipio
                            <span class="iglesia-obligatorio">*</span>
                        </label>

                        <select
                            id="municipio"
                            name="municipio"
                            required
                            data-seleccionado="{{ $municipioSeleccionado }}"
                        >
                            <option value="">
                                Selecciona un municipio
                            </option>

                            @foreach(
                                $estadosMunicipios[$estadoSeleccionado] ?? []
                                as $municipio
                            )
                                <option
                                    value="{{ $municipio }}"
                                    @selected($municipioSeleccionado === $municipio)
                                >
                                    {{ $municipio }}
                                </option>
                            @endforeach
                        </select>

                        <span class="iglesia-ayuda">
                            Los municipios se actualizan al cambiar el estado.
                        </span>

                        @error('municipio')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="iglesia-campo completo">
                        <label for="referencias">Referencias</label>

                        <textarea
                            id="referencias"
                            name="referencias"
                            rows="3"
                            maxlength="2000"
                            placeholder="Indicaciones para localizar la iglesia"
                        >{{ old('referencias', $direccion?->referencias) }}</textarea>

                        @error('referencias')
                            <span class="iglesia-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>
            </div>

            <!-- BOTONES -->

            <div class="iglesia-acciones">

                <a
                    href="{{ route('iglesias.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-check-circle"></i>

                    {{ $esEdicion
                        ? 'Guardar cambios'
                        : 'Registrar iglesia' }}
                </button>

            </div>

        </div>

    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const estadosMunicipios = @json($estadosMunicipios);

        const estadoSelect = document.getElementById('estado');
        const municipioSelect = document.getElementById('municipio');

        if (!estadoSelect || !municipioSelect) {
            return;
        }

        function actualizarMunicipios(municipioSeleccionado = '') {

            const estado = estadoSelect.value;

            municipioSelect.innerHTML = '';

            const opcionInicial = document.createElement('option');
            opcionInicial.value = '';
            opcionInicial.textContent = 'Selecciona un municipio';

            municipioSelect.appendChild(opcionInicial);

            const municipios = estadosMunicipios[estado] || [];

            municipios.forEach(function (municipio) {

                const opcion = document.createElement('option');

                opcion.value = municipio;
                opcion.textContent = municipio;

                if (municipio === municipioSeleccionado) {
                    opcion.selected = true;
                }

                municipioSelect.appendChild(opcion);
            });

            municipioSelect.disabled = estado === '';
        }

        estadoSelect.addEventListener('change', function () {
            actualizarMunicipios();
        });

        actualizarMunicipios(
            municipioSelect.dataset.seleccionado || ''
        );

    });
</script>

@endsection
