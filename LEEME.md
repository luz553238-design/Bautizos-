# Gestión de bautizos

Sistema Laravel 12 y Bootstrap 5.3.8 con 20 módulos correspondientes a todas las tablas del SQL compartido. Incluye consulta, búsqueda, paginación, creación, detalle, edición y eliminación, también para relaciones con claves compuestas.

## Abrir

Ejecuta `INICIAR.bat` y abre http://127.0.0.1:8000. Mantén abierta la consola mientras uses el sistema. No requiere Node ni internet para las vistas.

La configuración actual usa MySQL de XAMPP en 127.0.0.1:3306, base `gestion_bautizos`. Inicia MySQL desde XAMPP antes de usar el sistema. Las sesiones y la caché se guardan en archivos. El archivo anterior `database/database.sqlite` se conserva con sus datos, sin transferirlos automáticamente a MySQL. No ejecutes migraciones sobre las 20 tablas existentes.

## Usar MySQL / XAMPP

1. Inicia MySQL desde XAMPP.
2. Para una base NUEVA y vacía, configura `.env` con los valores siguientes y ejecuta `php artisan config:clear` y `php artisan migrate`. Crea primero la base `gestion_bautizos` en phpMyAdmin.

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_bautizos
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Si tu usuario tiene contraseña, úsala en DB_PASSWORD.

Si ya importaste el SQL original y existen sus 20 tablas, NO ejecutes la migración de bautizos sobre ellas: basta con cambiar `.env` y ejecutar `php artisan config:clear`, usando sesiones y caché en archivos como arriba. El SQL original se conserva en `database/gestion_bautizos.sql`.

## Orden sugerido

Registra primero personas, usuarios, roles y catálogos; después sacerdotes, tutores, padrinos y bautizados; continúa con solicitudes, relaciones, documentos, horarios, citas y bautismos. Los selectores muestran registros existentes con sus nombres.

Las contraseñas se guardan con hash y nunca se muestran. El módulo Documentos administra los metadatos y rutas que incluye el esquema; no incorpora carga de archivos. Las eliminaciones respetan RESTRICT, CASCADE y SET NULL del SQL. La interfaz pide confirmación y avisa de las eliminaciones en cascada.

El panel requiere iniciar sesión con nombre_usuario y contraseña de la tabla usuarios. En una base sin usuarios, la pantalla de acceso permite crear la primera cuenta; después esta opción se desactiva. Las cuentas inactivas no pueden entrar. Los usuarios activos tienen acceso a todos los módulos; los roles aún no restringen permisos. El servidor se enlaza únicamente a 127.0.0.1. Al entrar se muestra el menú principal; el botón Cerrar sesión termina la sesión.

## Verificar

`php artisan test` ejecuta pruebas de creación, consulta, edición y eliminación para los 20 módulos en una base temporal, además de duplicados, horarios inválidos, relaciones y contraseñas.
