
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis solicitudes de bautismo</title>
    <style>
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
        h1 { color: #67378d; }
        .cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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
        }
        .tarjeta {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 22px;
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            padding: 14px;
            border-bottom: 1px solid #e8dfee;
            text-align: left;
        }
        th { color: #63328b; }
        .estado {
            display: inline-block;
            border-radius: 20px;
            background: #fff0cf;
            padding: 7px 12px;
            font-weight: bold;
            font-size: 13px;
        }
        .mensaje {
            background: #e4f7e9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="contenedor">

    <div class="cabecera">
        <h1>Mis solicitudes de bautismo</h1>
        <a href="{{ route('solicitudes.crear') }}" class="boton">
            + Nueva solicitud
        </a>
    </div>

    @if(session('success'))
        <div class="mensaje">{{ session('success') }}</div>
    @endif

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
                <tr>
                    <td>{{ $solicitud->folio }}</td>
                    <td>{{ $solicitud->fecha_solicitud }}</td>
                    <td>{{ $solicitud->fecha ?? 'Por definir' }}</td>
                    <td>{{ $solicitud->hora_inicio ?? 'Por definir' }}</td>
                    <td>
                        <span class="estado">
                            {{ ucfirst($solicitud->estado) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        Todavía no tienes solicitudes registradas.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:20px">
            {{ $solicitudes->links() }}
        </div>
    </div>

    <p style="margin-top:25px">
        <a href="{{ route('dashboard') }}" class="boton">
            Volver al menú
        </a>
    </p>

</div>
</body>
</html>
