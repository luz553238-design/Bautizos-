
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menú de solicitudes - Bautizos</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f1fa;
            color: #30233e;
        }

        .contenedor {
            max-width: 1100px;
            margin: 50px auto;
            padding: 20px;
        }

        .cabecera {
            background: linear-gradient(120deg, #63328b, #9862bd);
            padding: 35px;
            border-radius: 18px;
            color: white;
            margin-bottom: 25px;
        }

        .cabecera h1 {
            margin: 0 0 10px;
        }

        .cabecera p {
            margin: 0;
            line-height: 1.6;
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .tarjeta {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 6px 20px #35224d12;
        }

        .tarjeta h2 {
            color: #67378d;
            margin-top: 0;
        }

        .tarjeta p {
            color: #675b70;
            line-height: 1.6;
        }

        .boton {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 20px;
            background: #75409e;
            color: white;
            border: none;
            border-radius: 9px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .boton:hover {
            background: #5d2c85;
        }

        .pie {
            margin-top: 25px;
            display: flex;
            justify-content: flex-end;
        }

        @media(max-width: 650px) {
            .tarjetas {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="cabecera">
        <h1>Sistema de Gestión de Bautizos</h1>
        <p>
            Bienvenido al panel de solicitudes.
            Aquí puedes registrar un bautismo y consultar
            las solicitudes que has realizado.
        </p>
    </header>

    <div class="tarjetas">

        <div class="tarjeta">
            <h2>Nueva solicitud</h2>
            <p>
                Registra los datos del menor, padres,
                padrinos, documentos y fecha de ceremonia.
            </p>

            <a class="boton"
               href="{{ route('solicitudes.crear') }}">
                Crear solicitud
            </a>
        </div>

        <div class="tarjeta">
            <h2>Mis solicitudes</h2>
            <p>
                Consulta tus solicitudes registradas,
                folios, fechas de ceremonia y estados.
            </p>

            <a class="boton"
               href="{{ route('solicitudes.mis') }}">
                Ver mis solicitudes
            </a>
        </div>

        <div class="tarjeta">
            <h2>Citas y horarios</h2>
            <p>
                Consulta las fechas y horarios de ceremonia
                desde el apartado Mis solicitudes.
            </p>

            <a class="boton"
               href="{{ route('solicitudes.mis') }}">
                Consultar horarios
            </a>
        </div>

        <div class="tarjeta">
            <h2>Seguimiento</h2>
            <p>
                Revisa si tu solicitud está pendiente
                o si ya fue revisada por el sacerdote.
            </p>

            <a class="boton"
               href="{{ route('solicitudes.mis') }}">
                Consultar estado
            </a>
        </div>

    </div>

    <div class="pie">
        <form method="POST" action="{{ url('/logout') }}">
            @csrf

            <button type="submit" class="boton">
                Cerrar sesión
            </button>
        </form>
    </div>

</div>
</body>
</html>
