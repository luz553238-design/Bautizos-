
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
            font-size: 14px;
            transition: background 0.2s;
        }

        .cerrar:hover {
            background: #f0e4f1;
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

        .bienvenida p {
            line-height: 1.7;
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
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition:
                transform 0.2s,
                box-shadow 0.2s;
        }

        .tarjeta:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px #00000012;
        }

        .tarjeta h3 {
            color: #66466f;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .tarjeta p {
            line-height: 1.6;
            color: #6f6574;
            flex-grow: 1;
        }

        .icono {
            width: 48px;
            height: 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f0e4f1;
            color: #66466f;
            border-radius: 12px;
            font-size: 24px;
        }

        .boton {
            display: inline-block;
            margin-top: 12px;
            padding: 11px 18px;
            border-radius: 8px;
            background: #735475;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .boton:hover {
            background: #563c5a;
        }

        .boton:focus-visible,
        .cerrar:focus-visible {
            outline: 3px solid #b99ac4;
            outline-offset: 3px;
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

        .mensaje {
            margin-top: 22px;
            padding: 15px 20px;
            border-radius: 10px;
        }

        .mensaje-exito {
            background: #d8eee2;
            border: 1px solid #a8d3b8;
            color: #20553a;
        }

        @media (max-width: 600px) {
            .barra {
                flex-direction: column;
                text-align: center;
            }

            .bienvenida {
                padding: 25px;
            }

            .bienvenida h1 {
                font-size: 25px;
            }

            .tarjetas {
                grid-template-columns: 1fr;
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

    <!-- BIENVENIDA -->
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

    <!-- MENSAJES -->
    @if (session('success'))
        <div class="mensaje mensaje-exito">
            {{ session('success') }}
        </div>
    @endif

    <!-- OPCIONES DEL CLIENTE -->
    <section class="tarjetas">

        <!-- NUEVA SOLICITUD -->
        <article class="tarjeta">

            <div class="icono" aria-hidden="true">
                +
            </div>

            <h3>Nueva solicitud</h3>

            <p>
                Inicia el trámite para solicitar
                un bautismo. Completa los datos
                del menor, padres, padrinos,
                ceremonia y plática prebautismal.
            </p>

            <a href="{{ route('solicitudes.crear') }}"
               class="boton">
                Crear solicitud
            </a>

        </article>

        <!-- MIS SOLICITUDES -->
        <article class="tarjeta">

            <div class="icono" aria-hidden="true">
                &#9776;
            </div>

            <h3>Mis solicitudes</h3>

            <p>
                Consulta el estado de las
                solicitudes registradas,
                las fechas seleccionadas
                y las propuestas de cambio
                enviadas por el sacerdote.
            </p>

            <a href="{{ route('solicitudes.mis') }}"
               class="boton">
                Ver solicitudes
            </a>

        </article>

        <!-- MIS DOCUMENTOS -->
        <article class="tarjeta">

            <div class="icono" aria-hidden="true">
                &#128196;
            </div>

            <h3>Mis documentos</h3>

            <p>
                Consulta los documentos
                relacionados con tus trámites,
                como actas de nacimiento
                y certificados de matrimonio.
            </p>

            <span class="etiqueta">
                Próximamente
            </span>

        </article>

        <!-- MIS CITAS -->
        <article class="tarjeta">

            <div class="icono" aria-hidden="true">
                &#128197;
            </div>

            <h3>Mis citas</h3>

            <p>
                Consulta las fechas y horarios
                de tus próximas ceremonias
                y pláticas prebautismales.
            </p>

            <span class="etiqueta">
                Próximamente
            </span>

        </article>

    </section>

</main>

</body>
</html>
