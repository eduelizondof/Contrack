<?php

use App\Http\Controllers\Expedientes\ControladorDatosInquilinos;
use App\Http\Controllers\Expedientes\ControladorExpedientes;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Expedientes
|--------------------------------------------------------------------------
|
| Gestión de documentos y datos adicionales de inquilinos
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // CRUD de expedientes (documentos)
    Route::prefix('expedientes')->name('expedientes.')->group(function () {
        Route::get('/', [ControladorExpedientes::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorExpedientes::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
        Route::post('/', [ControladorExpedientes::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorExpedientes::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorExpedientes::class, 'eliminar'])->name('eliminar');
        
        // Verificación de documentos
        Route::post('/{id}/verificar', [ControladorExpedientes::class, 'verificar'])->name('verificar');
        
        // Expedientes por usuario
        Route::get('/usuario/{usuarioId}', [ControladorExpedientes::class, 'porUsuario'])->name('por-usuario');
    });
    
    // Datos adicionales de inquilinos
    Route::prefix('datos-inquilinos')->name('datos-inquilinos.')->group(function () {
        Route::get('/{usuarioId}', [ControladorDatosInquilinos::class, 'mostrar'])->name('mostrar');
        Route::put('/{usuarioId}', [ControladorDatosInquilinos::class, 'almacenarOActualizar'])->name('guardar');
    });
});
