
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Models\Usuario;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\SolicitudBautismoController;
use App\Http\Controllers\AdministracionPermisosController;
use App\Http\Controllers\RevisionBautismoController;
use App\Http\Controllers\RegistroBautismoController;
use App\Http\Controllers\CertificadoBautismoController;
use App\Http\Controllers\IglesiaController;

/*
|--------------------------------------------------------------------------
| FUNCIONES PARA IDENTIFICAR USUARIOS Y ROLES
|--------------------------------------------------------------------------
*/

$obtenerUsuario = function (): Usuario {

    $usuario = Auth::user();

    if (!$usuario instanceof Usuario) {
        abort(401, 'Debes iniciar sesión.');
    }

    return $usuario;
};

$esPersonalAdministrativo = function (Usuario $usuario): bool {

    return $usuario->roles()
        ->whereIn('roles.nombre', [
            'superadministrador',
            'administrador',
            'secretaria',
            'sacerdote'
        ])
        ->exists();
};

$esCliente = function (Usuario $usuario): bool {

    return $usuario->roles()
        ->whereIn('roles.nombre', [
            'cliente',
            'padre_tutor',
            'padrino'
        ])
        ->exists();
};

/*
|--------------------------------------------------------------------------
| RUTA PRINCIPAL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (Auth::check()) {
        return redirect()->route('panel.redirect');
    }

    return redirect()->route('login');

})->name('inicio');

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN Y REGISTRO
|--------------------------------------------------------------------------
*/

Route::middleware('guest')
    ->controller(AuthController::class)
    ->group(function () {

        Route::get('/login', 'form')
            ->name('login');

        Route::post('/login', 'login')
            ->middleware('throttle:5,1')
            ->name('login.store');

        Route::get('/crear-cuenta', 'registration')
            ->name('register');

        Route::post('/crear-cuenta', 'register')
            ->middleware('throttle:5,1')
            ->name('register.store');

        Route::post('/primer-usuario', 'setup')
            ->middleware('throttle:5,1')
            ->name('setup');

    });

/*
|--------------------------------------------------------------------------
| CERRAR SESIÓN
|--------------------------------------------------------------------------
*/

Route::post('/logout', [
    AuthController::class,
    'logout'
])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| REDIRECCIÓN SEGÚN EL ROL
|--------------------------------------------------------------------------
| Personal administrativo: /menu
| Clientes: /solicitudes/menu
|--------------------------------------------------------------------------
*/

Route::get('/redirigir-panel', function () use (
    $obtenerUsuario,
    $esPersonalAdministrativo,
    $esCliente
) {

    $usuario = $obtenerUsuario();

    if ($esPersonalAdministrativo($usuario)) {
        return redirect()->route('dashboard');
    }

    if ($esCliente($usuario)) {
        return redirect()->route('solicitudes.menu');
    }

    abort(
        403,
        'Tu usuario no tiene un rol autorizado.'
    );

})
    ->middleware('auth')
    ->name('panel.redirect');

/*
|--------------------------------------------------------------------------
| PANEL ADMINISTRATIVO
|--------------------------------------------------------------------------
*/

Route::get('/menu', function () use (
    $obtenerUsuario,
    $esPersonalAdministrativo
) {

    $usuario = $obtenerUsuario();

    if (!$esPersonalAdministrativo($usuario)) {
        return redirect()->route('solicitudes.menu');
    }

    return app(CrudController::class)->dashboard();

})
    ->middleware('auth')
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| PANEL DE CLIENTES EXISTENTE
|--------------------------------------------------------------------------
*/

Route::get('/cliente', [
    ClienteController::class,
    'dashboard'
])
    ->middleware('auth')
    ->name('cliente.dashboard');

/*
|--------------------------------------------------------------------------
| MENÚ DE SOLICITUDES DE BAUTISMO
|--------------------------------------------------------------------------
*/

Route::get('/solicitudes/menu', function () use (
    $obtenerUsuario,
    $esPersonalAdministrativo,
    $esCliente
) {

    $usuario = $obtenerUsuario();

    if (
        !$esCliente($usuario) &&
        !$esPersonalAdministrativo($usuario)
    ) {
        abort(
            403,
            'No tienes permiso para acceder a las solicitudes.'
        );
    }

    return view('solicitudes.menu');

})
    ->middleware('auth')
    ->name('solicitudes.menu');

/*
|--------------------------------------------------------------------------
| SOLICITUDES DE BAUTISMO
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('solicitudes')
    ->name('solicitudes.')
    ->controller(SolicitudBautismoController::class)
    ->group(function () {

        // Mostrar formulario de nueva solicitud
        Route::get('/nueva', 'crear')
            ->name('crear');

        // Guardar nueva solicitud
        Route::post('/', 'guardar')
            ->name('guardar');

        // Consultar solicitudes del usuario
        Route::get('/mis', 'misSolicitudes')
            ->name('mis');

        // Descargar documentos de una solicitud
        Route::get(
            '/documentos/{id}',
            'descargarDocumento'
        )
            ->whereNumber('id')
            ->name('documentos.descargar');

        // Responder propuesta de cambio de cita
        Route::post(
            '/propuestas/{id}/responder',
            'responderPropuesta'
        )
            ->whereNumber('id')
            ->name('propuestas.responder');

    });

/*
|--------------------------------------------------------------------------
| NUEVO MÓDULO: IGLESIAS Y DIRECCIONES
|--------------------------------------------------------------------------
| Permite registrar varias iglesias, cada una con su
| dirección, consultar el listado y editar sus datos.
|
| IglesiaController verifica que el usuario tenga
| el rol de superadministrador o administrador.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('iglesias')
    ->name('iglesias.')
    ->controller(IglesiaController::class)
    ->group(function () {

        // Mostrar todas las iglesias
        Route::get('/', 'index')
            ->name('index');

        // Formulario para registrar iglesia
        Route::get('/crear', 'create')
            ->name('create');

        // Guardar iglesia y dirección
        Route::post('/', 'store')
            ->name('store');

        // Formulario para editar iglesia
        Route::get('/{iglesia}/editar', 'edit')
            ->whereNumber('iglesia')
            ->name('edit');

        // Actualizar iglesia y dirección
        Route::put('/{iglesia}', 'update')
            ->whereNumber('iglesia')
            ->name('update');

        // Eliminar iglesia y dirección
        Route::delete('/{iglesia}', 'destroy')
            ->whereNumber('iglesia')
            ->name('destroy');

    });

/*
|--------------------------------------------------------------------------
| ADMINISTRACIÓN CRUD
|--------------------------------------------------------------------------
| El controlador valida los permisos de cada módulo.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('gestion/{module}')
    ->name('crud.')
    ->controller(CrudController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index');

        Route::get('/crear', 'create')
            ->name('create');

        Route::post('/', 'store')
            ->name('store');

        Route::get('/{record}/editar', 'edit')
            ->name('edit');

        Route::get('/{record}', 'show')
            ->name('show');

        Route::put('/{record}', 'update')
            ->name('update');

        Route::delete('/{record}', 'destroy')
            ->name('destroy');

    });

/*
|--------------------------------------------------------------------------
| ADMINISTRACIÓN DE ROLES Y PERMISOS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('administracion')
    ->name('admin.')
    ->controller(AdministracionPermisosController::class)
    ->group(function () {

        // Mostrar permisos por rol
        Route::get(
            '/permisos',
            'index'
        )->name('permisos');

        // Actualizar permisos
        Route::put(
            '/permisos/{idRol}',
            'actualizarPermisos'
        )
            ->whereNumber('idRol')
            ->name('permisos.actualizar');

        // Administrar usuarios
        Route::get(
            '/usuarios',
            'usuarios'
        )->name('usuarios');

        // Cambiar rol de usuario
        Route::put(
            '/usuarios/{idUsuario}/rol',
            'actualizarRol'
        )
            ->whereNumber('idUsuario')
            ->name('usuarios.rol');

        // Activar o desactivar usuario
        Route::patch(
            '/usuarios/{idUsuario}/estado',
            'cambiarEstado'
        )
            ->whereNumber('idUsuario')
            ->name('usuarios.estado');

    });

/*
|--------------------------------------------------------------------------
| REVISIÓN DE SOLICITUDES DE BAUTISMO
|--------------------------------------------------------------------------
| Permite revisar, aprobar, rechazar y proponer
| cambios de fecha en las solicitudes.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('revision-bautizos')
    ->name('revision.')
    ->controller(RevisionBautismoController::class)
    ->group(function () {

        // Listado de solicitudes
        Route::get('/', 'index')
            ->name('index');

        // Detalle de una solicitud
        Route::get('/{id}', 'show')
            ->whereNumber('id')
            ->name('show');

        // Aprobar solicitud
        Route::post('/{id}/aprobar', 'aprobar')
            ->whereNumber('id')
            ->name('aprobar');

        // Rechazar solicitud
        Route::post('/{id}/rechazar', 'rechazar')
            ->whereNumber('id')
            ->name('rechazar');

        // Proponer cambio de fecha
        Route::post(
            '/{id}/proponer-cambio',
            'proponerCambio'
        )
            ->whereNumber('id')
            ->name('proponer');

    });

/*
|--------------------------------------------------------------------------
| REGISTRO DE BAUTISMOS REALIZADOS
|--------------------------------------------------------------------------
| Permite consultar solicitudes aprobadas, registrar
| ceremonias realizadas y consultar el historial.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('registro-bautismos')
    ->name('bautismos.')
    ->controller(RegistroBautismoController::class)
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | LISTADO DE BAUTISMOS
        |--------------------------------------------------------------------------
        */

        Route::get('/', 'index')
            ->name('index');

        /*
        |--------------------------------------------------------------------------
        | HISTORIAL DE BAUTISMOS
        |--------------------------------------------------------------------------
        | Muestra los bautismos registrados y permite
        | buscar y descargar sus certificados PDF.
        |--------------------------------------------------------------------------
        */

        Route::get('/historial', 'historial')
            ->name('historial');

        /*
        |--------------------------------------------------------------------------
        | FORMULARIO PARA REGISTRAR BAUTISMO
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/{id}/registrar',
            'crear'
        )
            ->whereNumber('id')
            ->name('crear');

        /*
        |--------------------------------------------------------------------------
        | GUARDAR BAUTISMO REALIZADO
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/{id}/registrar',
            'guardar'
        )
            ->whereNumber('id')
            ->name('guardar');

    });

/*
|--------------------------------------------------------------------------
| DESCARGAR CERTIFICADO DE BAUTISMO
|--------------------------------------------------------------------------
| Permite descargar el certificado PDF generado
| a partir de un bautismo registrado.
|
| La autorización se verifica en:
| CertificadoBautismoController@descargar
|--------------------------------------------------------------------------
*/

Route::get(
    '/certificados/bautismos/{idBautismo}',
    [
        CertificadoBautismoController::class,
        'descargar'
    ]
)
    ->middleware('auth')
    ->whereNumber('idBautismo')
    ->name('certificados.bautismos.descargar');
