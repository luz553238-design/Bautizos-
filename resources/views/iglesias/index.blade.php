
@extends('layouts.app')

@section('title', 'Iglesias y direcciones')

@section('content')

<style>
    .iglesias-contenedor {
        max-width: 1400px;
        margin: 0 auto;
    }

    .iglesias-encabezado {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 25px;
    }

    .iglesias-encabezado h1 {
        color: #34263f;
        font-size: 27px;
        font-weight: 800;
        margin: 0 0 8px;
    }

    .iglesias-encabezado p {
        color: #777589;
        margin: 0;
    }

    .iglesias-tarjeta {
        background: white;
        border: 1px solid #e9e5ee;
        border-radius: 16px;
        overflow: hidden;
    }

    .iglesias-buscador {
        display: flex;
        gap: 12px;
        padding: 22px;
        border-bottom: 1px solid #e9e5ee;
    }

    .iglesias-buscador input {
        flex: 1;
        min-width: 0;
        border: 1px solid #ddd7e5;
        border-radius: 9px;
        padding: 11px 13px;
    }

    .iglesias-tabla {
        width: 100%;
        border-collapse: collapse;
    }

    .iglesias-tabla th,
    .iglesias-tabla td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid #eee9f1;
        vertical-align: middle;
    }

    .iglesias-tabla th {
        background: #faf8fc;
        font-weight: 800;
        color: #51435b;
    }

    .iglesias-nombre {
        font-weight: 800;
        color: #34263f;
    }

    .iglesias-detalle {
        color: #777589;
        font-size: 12px;
        margin-top: 5px;
    }

    .iglesias-estado {
        display: inline-block;
        border-radius: 20px;
        padding: 5px 12px;
        font-weight: 700;
        font-size: 12px;
    }

    .iglesias-estado.activa {
        background: #e8f6ed;
        color: #237346;
    }

    .iglesias-estado.inactiva {
        background: #f3ebeb;
        color: #8b4c4c;
    }

    .iglesias-acciones {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .iglesias-paginacion {
        padding: 20px;
    }

    @media(max-width: 700px) {
        .iglesias-buscador {
            flex-direction: column;
        }

        .iglesias-tabla {
            min-width: 850px;
        }
    }
</style>

<div class="iglesias-contenedor">

    <div class="iglesias-encabezado">

        <div>
            <h1>
                <i class="bi bi-buildings"></i>
                Iglesias y direcciones
            </h1>

            <p>
                Administración de iglesias, parroquias,
                capillas y sus ubicaciones.
            </p>
        </div>

        <a
            href="{{ route('iglesias.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-plus-circle"></i>
            Registrar iglesia
        </a>

    </div>

    <div class="iglesias-tarjeta">

        <form
            action="{{ route('iglesias.index') }}"
            method="GET"
            class="iglesias-buscador"
        >
            <input
                type="text"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Buscar iglesia, municipio, colonia o estado..."
            >

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i>
                Buscar
            </button>

            @if($buscar !== '')
                <a
                    href="{{ route('iglesias.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Limpiar
                </a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="iglesias-tabla">

                <thead>
                    <tr>
                        <th>Iglesia</th>
                        <th>Tipo</th>
                        <th>Dirección</th>
                        <th>Municipio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($iglesias as $iglesia)

                        <tr>
                            <td>
                                <div class="iglesias-nombre">
                                    {{ $iglesia->nombre }}
                                </div>

                                <div class="iglesias-detalle">
                                    {{ $iglesia->telefono ?: 'Sin teléfono' }}
                                </div>
                            </td>

                            <td>
                                {{ $iglesia->tipo }}
                            </td>

                            <td>
                                @if($iglesia->direccion)
                                    {{ $iglesia->direccion->calle }}
                                    {{ $iglesia->direccion->numero_exterior }}

                                    @if($iglesia->direccion->numero_interior)
                                        Int. {{ $iglesia->direccion->numero_interior }}
                                    @endif

                                    <div class="iglesias-detalle">
                                        {{ $iglesia->direccion->colonia }},
                                        C.P. {{ $iglesia->direccion->codigo_postal }}
                                    </div>
                                @else
                                    Sin dirección
                                @endif
                            </td>

                            <td>
                                {{ $iglesia->direccion?->municipio ?? '—' }}
                            </td>

                            <td>
                                <span class="iglesias-estado {{ $iglesia->activo ? 'activa' : 'inactiva' }}">
                                    {{ $iglesia->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>

                            <td>
                                <div class="iglesias-acciones">

                                    <a
                                        href="{{ route('iglesias.edit', $iglesia) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('iglesias.destroy', $iglesia) }}"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar esta iglesia y su dirección?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            <i class="bi bi-trash"></i>
                                            Eliminar
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" style="text-align:center; padding:45px;">
                                <i class="bi bi-buildings" style="font-size:32px; color:#694b76;"></i>

                                <p style="margin-top:12px; color:#777589;">
                                    No se encontraron iglesias registradas.
                                </p>
                            </td>
                        </tr>

                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="iglesias-paginacion">
            {{ $iglesias->links() }}
        </div>

    </div>

</div>

@endsection
