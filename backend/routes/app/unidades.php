<?php

use App\Http\Controllers\Unidades\ControladorInmuebleUsuarios;
use App\Http\Controllers\Unidades\ControladorUnidades;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Unidades
|--------------------------------------------------------------------------
|
| Gestión de unidades habitacionales y asignación de usuarios
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // CRUD de unidades
    Route::prefix('unidades')->name('unidades.')->group(function () {
        Route::get('/', [ControladorUnidades::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorUnidades::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
        Route::post('/', [ControladorUnidades::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorUnidades::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorUnidades::class, 'eliminar'])->name('eliminar');
        
        // Consulta rápida de adeudos
        Route::get('/{id}/adeudos', [ControladorUnidades::class, 'adeudos'])->name('adeudos');
    });
    
    // Asignación de usuarios a inmuebles/unidades
    Route::prefix('inmueble-usuarios')->name('inmueble-usuarios.')->group(function () {
        Route::get('/', [ControladorInmuebleUsuarios::class, 'index'])->name('index');
        Route::post('/', [ControladorInmuebleUsuarios::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorInmuebleUsuarios::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorInmuebleUsuarios::class, 'eliminar'])->name('eliminar');
        
        // Consultas específicas
        Route::get('/por-inmueble/{inmuebleId}', [ControladorInmuebleUsuarios::class, 'porInmueble'])->name('por-inmueble');
        Route::get('/por-unidad/{unidadId}', [ControladorInmuebleUsuarios::class, 'porUnidad'])->name('por-unidad');
        Route::get('/usuarios-disponibles/{inmuebleId}', [ControladorInmuebleUsuarios::class, 'usuariosDisponibles'])->name('usuarios-disponibles');
    });
});
