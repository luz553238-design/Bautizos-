
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
|
| Administradores, secretarias y sacerdotes:
|     /menu
|
| Clientes, padres y padrinos:
|     /solicitudes/menu
|
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
|
| Se conserva la ruta original para evitar romper
| enlaces y funciones que ya la utilizan.
|
*/

Route::get('/cliente', [
    ClienteController::class,
    'dashboard'
])
    ->middleware('auth')
    ->name('cliente.dashboard');

/*
|--------------------------------------------------------------------------
| NUEVO MENÚ DE SOLICITUDES DE BAUTISMO
|--------------------------------------------------------------------------
|
| Permite acceder a:
| - Nueva solicitud
| - Mis solicitudes
| - Consultar fechas y horarios
| - Seguimiento de solicitudes
|
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

        /*
        |--------------------------------------------------------------
        | NUEVA SOLICITUD
        |--------------------------------------------------------------
        */

        Route::get('/nueva', 'crear')
            ->name('crear');

        /*
        |--------------------------------------------------------------
        | GUARDAR SOLICITUD
        |--------------------------------------------------------------
        */

        Route::post('/', 'guardar')
            ->name('guardar');

        /*
        |--------------------------------------------------------------
        | MIS SOLICITUDES
        |--------------------------------------------------------------
        */

        Route::get('/mis', 'misSolicitudes')
            ->name('mis');

        /*
        |--------------------------------------------------------------
        | DESCARGAR DOCUMENTOS
        |--------------------------------------------------------------
        */

        Route::get(
            '/documentos/{id}',
            'descargarDocumento'
        )
            ->whereNumber('id')
            ->name('documentos.descargar');

        /*
        |--------------------------------------------------------------
        | RESPONDER PROPUESTA DEL SACERDOTE
        |--------------------------------------------------------------
        */

        Route::post(
            '/propuestas/{id}/responder',
            'responderPropuesta'
        )
            ->whereNumber('id')
            ->name('propuestas.responder');
    });

/*
|--------------------------------------------------------------------------
| ADMINISTRACIÓN CRUD
|--------------------------------------------------------------------------
|
| El controlador debe seguir validando los permisos
| de cada módulo y operación.
|
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
|
| El controlador debe verificar que el usuario
| tenga autorización para cada operación.
|
*/

Route::middleware('auth')
    ->prefix('administracion')
    ->name('admin.')
    ->controller(AdministracionPermisosController::class)
    ->group(function () {

        Route::get(
            '/permisos',
            'index'
        )->name('permisos');

        Route::put(
            '/permisos/{idRol}',
            'actualizarPermisos'
        )->name('permisos.actualizar');

        Route::get(
            '/usuarios',
            'usuarios'
        )->name('usuarios');

        Route::put(
            '/usuarios/{idUsuario}/rol',
            'actualizarRol'
        )->name('usuarios.rol');

        Route::patch(
            '/usuarios/{idUsuario}/estado',
            'cambiarEstado'
        )->name('usuarios.estado');
    });
/*
|--------------------------------------------------------------------------
| REVISIÓN DE SOLICITUDES POR EL SACERDOTE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('revision-bautizos')
    ->name('revision.')
    ->controller(RevisionBautismoController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index');

        Route::get('/{id}', 'show')
            ->whereNumber('id')
            ->name('show');

        Route::post('/{id}/aprobar', 'aprobar')
            ->whereNumber('id')
            ->name('aprobar');

        Route::post('/{id}/rechazar', 'rechazar')
            ->whereNumber('id')
            ->name('rechazar');

        Route::post('/{id}/proponer-cambio', 'proponerCambio')
            ->whereNumber('id')
            ->name('proponer');
    });