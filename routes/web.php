<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::middleware('guest')->controller(\App\Http\Controllers\AuthController::class)->group(function () {
    Route::get('/login','form')->name('login');
    Route::post('/login','login')->name('login.store');
    Route::get('/crear-cuenta','registration')->name('register');
    Route::post('/crear-cuenta','register')->middleware('throttle:5,1')->name('register.store');
    Route::post('/primer-usuario','setup')->middleware('throttle:5,1')->name('setup');
});
Route::post('/logout',[\App\Http\Controllers\AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/menu', [\App\Http\Controllers\CrudController::class, 'dashboard'])->middleware('auth')->name('dashboard');
Route::middleware('auth')->prefix('gestion/{module}')->name('crud.')->controller(\App\Http\Controllers\CrudController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/crear', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{record}/editar', 'edit')->name('edit');
    Route::get('/{record}', 'show')->name('show');
    Route::put('/{record}', 'update')->name('update');
    Route::delete('/{record}', 'destroy')->name('destroy');
});
