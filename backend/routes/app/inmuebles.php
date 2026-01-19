<?php

use App\Http\Controllers\Inmuebles\ControladorCuentasBancarias;
use App\Http\Controllers\Inmuebles\ControladorInmuebles;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Inmuebles
|--------------------------------------------------------------------------
|
| Gestión de inmuebles (condominios, fraccionamientos, edificios)
| y sus cuentas bancarias asociadas
|
*/

Route::prefix('inmuebles')->middleware('auth:sanctum')->name('inmuebles.')->group(function () {
    // CRUD de inmuebles
    Route::get('/', [ControladorInmuebles::class, 'index'])->name('index');
    Route::get('/{id}', [ControladorInmuebles::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
    Route::post('/', [ControladorInmuebles::class, 'almacenar'])->name('almacenar');
    Route::put('/{id}', [ControladorInmuebles::class, 'actualizar'])->name('actualizar');
    Route::delete('/{id}', [ControladorInmuebles::class, 'eliminar'])->name('eliminar');
    
    // Resumen del inmueble con estadísticas
    Route::get('/{id}/resumen', [ControladorInmuebles::class, 'resumen'])->name('resumen');
    
    // Cuentas bancarias del inmueble
    Route::prefix('/{inmuebleId}/cuentas-bancarias')->name('cuentas-bancarias.')->group(function () {
        Route::get('/', [ControladorCuentasBancarias::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorCuentasBancarias::class, 'mostrar'])->name('mostrar');
        Route::post('/', [ControladorCuentasBancarias::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorCuentasBancarias::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorCuentasBancarias::class, 'eliminar'])->name('eliminar');
    });
});
