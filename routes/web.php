
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Models\Usuario;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\ClienteController;

/*
|--------------------------------------------------------------------------
| FUNCION PARA IDENTIFICAR AL USUARIO AUTENTICADO
|--------------------------------------------------------------------------
*/

$obtenerUsuario = function (): Usuario {

    $usuario = Auth::user();

    if (!$usuario instanceof Usuario) {
        abort(401, 'Debes iniciar sesion.');
    }

    return $usuario;
};

/*
|--------------------------------------------------------------------------
| RUTA PRINCIPAL
|--------------------------------------------------------------------------
|
| Si el usuario esta autenticado, se redirige a su panel.
| Si no esta autenticado, se muestra el inicio de sesion.
|
*/

Route::get('/', function () {

    if (Auth::check()) {
        return redirect()->route('panel.redirect');
    }

    return redirect()->route('login');

})->name('inicio');


/*
|--------------------------------------------------------------------------
| RUTAS PARA USUARIOS NO AUTENTICADOS
|--------------------------------------------------------------------------
|
| Inicio de sesion, registro y configuracion inicial.
|
*/

Route::middleware('guest')
    ->controller(AuthController::class)
    ->group(function () {

        // Mostrar formulario de inicio de sesion.
        Route::get('/login', 'form')
            ->name('login');

        // Procesar inicio de sesion.
        Route::post('/login', 'login')
            ->name('login.store');

        // Mostrar formulario de registro.
        Route::get('/crear-cuenta', 'registration')
            ->name('register');

        // Registrar usuario nuevo.
        Route::post('/crear-cuenta', 'register')
            ->middleware('throttle:5,1')
            ->name('register.store');

        // Crear primer superadministrador.
        Route::post('/primer-usuario', 'setup')
            ->middleware('throttle:5,1')
            ->name('setup');
    });


/*
|--------------------------------------------------------------------------
| CERRAR SESION
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
| REDIRECCION SEGUN EL ROL
|--------------------------------------------------------------------------
|
| Superadministrador, administrador, secretaria y sacerdote:
| Panel administrativo.
|
| Cliente:
| Panel exclusivo de clientes.
|
| Padre tutor y padrino:
| Se redirigen al panel de cliente cuando no tienen otro rol.
|
*/

Route::get('/redirigir-panel', function () use ($obtenerUsuario) {

    $usuario = $obtenerUsuario();

    // Comprobar si pertenece al personal administrativo.
    $esPersonal = $usuario->roles()
        ->whereIn('roles.nombre', [
            'superadministrador',
            'administrador',
            'secretaria',
            'sacerdote'
        ])
        ->exists();

    if ($esPersonal) {
        return redirect()->route('dashboard');
    }

    // Comprobar si tiene rol de cliente.
    $esCliente = $usuario->roles()
        ->whereIn('roles.nombre', [
            'cliente',
            'padre_tutor',
            'padrino'
        ])
        ->exists();

    if ($esCliente) {
        return redirect()->route('cliente.dashboard');
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
|
| Solo puede acceder el personal autorizado.
|
*/

Route::get('/menu', function () use ($obtenerUsuario) {

    $usuario = $obtenerUsuario();

    $esPersonal = $usuario->roles()
        ->whereIn('roles.nombre', [
            'superadministrador',
            'administrador',
            'secretaria',
            'sacerdote'
        ])
        ->exists();

    if (!$esPersonal) {
        abort(
            403,
            'No tienes permiso para acceder al panel administrativo.'
        );
    }

    return app(CrudController::class)->dashboard();

})
    ->middleware('auth')
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| PANEL EXCLUSIVO PARA CLIENTES
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
| RUTAS DE ADMINISTRACION CRUD
|--------------------------------------------------------------------------
|
| Todas las operaciones requieren autenticacion.
| Los permisos especificos se validan en CrudController.
|
*/

Route::middleware('auth')
    ->prefix('gestion/{module}')
    ->name('crud.')
    ->controller(CrudController::class)
    ->group(function () {

        // Mostrar registros.
        Route::get('/', 'index')
            ->name('index');

        // Mostrar formulario de creacion.
        Route::get('/crear', 'create')
            ->name('create');

        // Guardar registro nuevo.
        Route::post('/', 'store')
            ->name('store');

        // Mostrar formulario de edicion.
        Route::get('/{record}/editar', 'edit')
            ->name('edit');

        // Mostrar detalles del registro.
        Route::get('/{record}', 'show')
            ->name('show');

        // Actualizar registro.
        Route::put('/{record}', 'update')
            ->name('update');

        // Eliminar registro.
        Route::delete('/{record}', 'destroy')
            ->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| ADMINISTRACION DE ROLES Y PERMISOS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('administracion')
    ->name('admin.')
    ->controller(
        \App\Http\Controllers\AdministracionPermisosController::class
    )
    ->group(function () {

        // Mostrar matriz de permisos.
        Route::get(
            '/permisos',
            'index'
        )->name('permisos');

        // Actualizar permisos de un rol.
        Route::put(
            '/permisos/{idRol}',
            'actualizarPermisos'
        )->name('permisos.actualizar');

        // Mostrar usuarios.
        Route::get(
            '/usuarios',
            'usuarios'
        )->name('usuarios');

        // Cambiar rol de un usuario.
        Route::put(
            '/usuarios/{idUsuario}/rol',
            'actualizarRol'
        )->name('usuarios.rol');

        // Activar o desactivar usuario.
        Route::patch(
            '/usuarios/{idUsuario}/estado',
            'cambiarEstado'
        )->name('usuarios.estado');

    });
