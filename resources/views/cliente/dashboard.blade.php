
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Panel de Cliente - Bautizos</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f3f8;
            color: #30243b;
        }

        .barra {
            background: #34263e;
            color: white;
            padding: 20px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .barra h2 {
            margin: 0;
        }

        .cerrar {
            padding: 10px 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            color: #34263e;
            cursor: pointer;
        }

        .contenedor {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .bienvenida {
            padding: 35px;
            border-radius: 18px;
            background: linear-gradient(
                120deg,
                #f0e4f1,
                #f9edf0
            );
        }

        .bienvenida h1 {
            margin-top: 0;
            font-size: 32px;
        }

        .tarjetas {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .tarjeta {
            background: white;
            padding: 28px;
            border-radius: 16px;
            border: 1px solid #e9e1ec;
            box-shadow: 0 5px 15px #00000008;
        }

        .tarjeta h3 {
            color: #66466f;
        }

        .tarjeta p {
            line-height: 1.6;
            color: #6f6574;
        }

        .etiqueta {
            display: inline-block;
            margin-top: 12px;
            padding: 9px 14px;
            border-radius: 8px;
            background: #eee7f0;
            color: #66466f;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .barra {
                flex-direction: column;
            }

            .bienvenida h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<header class="barra">
    <h2>Sistema de Gestión de Bautizos</h2>

    <form method="POST"
          action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="cerrar">
            Cerrar sesión
        </button>
    </form>
</header>

<main class="contenedor">

    <section class="bienvenida">

        <h1>
            Bienvenido,
            {{ $usuario->nombre_usuario }}
        </h1>

        <p>
            Desde este espacio podrás gestionar
            tus trámites de bautismo y consultar
            la información relacionada con tus
            solicitudes.
        </p>

        <strong>Rol: Cliente</strong>

    </section>

    <section class="tarjetas">

        <article class="tarjeta">
            <h3>Nueva solicitud</h3>

            <p>
                Inicia el trámite para solicitar
                un bautismo.
            </p>

            <span class="etiqueta">
                Próximamente
            </span>
        </article>

        <article class="tarjeta">
            <h3>Mis solicitudes</h3>

            <p>
                Consulta el estado de las
                solicitudes que hayas registrado.
            </p>

            <span class="etiqueta">
                Próximamente
            </span>
        </article>

        <article class="tarjeta">
            <h3>Mis documentos</h3>

            <p>
                Consulta los documentos
                relacionados con tus trámites.
            </p>

            <span class="etiqueta">
                Próximamente
            </span>
        </article>

        <article class="tarjeta">
            <h3>Mis citas</h3>

            <p>
                Consulta las fechas y horarios
                de tus próximas citas.
            </p>

            <span class="etiqueta">
                Próximamente
            </span>
        </article>

    </section>

</main>

</body>
</html>
