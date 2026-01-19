<?php

use App\Http\Controllers\Finanzas\ControladorCargos;
use App\Http\Controllers\Finanzas\ControladorConceptos;
use App\Http\Controllers\Finanzas\ControladorPagos;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Finanzas
|--------------------------------------------------------------------------
|
| Gestión de conceptos de cobro, cargos y pagos
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Conceptos de cobro
    Route::prefix('conceptos')->name('conceptos.')->group(function () {
        Route::get('/', [ControladorConceptos::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorConceptos::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
        Route::post('/', [ControladorConceptos::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorConceptos::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorConceptos::class, 'eliminar'])->name('eliminar');
    });
    
    // Cargos (adeudos)
    Route::prefix('cargos')->name('cargos.')->group(function () {
        Route::get('/', [ControladorCargos::class, 'index'])->name('index');
        Route::get('/pendientes', [ControladorCargos::class, 'pendientes'])->name('pendientes');
        Route::get('/vencidos', [ControladorCargos::class, 'vencidos'])->name('vencidos');
        Route::get('/{id}', [ControladorCargos::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
        Route::post('/', [ControladorCargos::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorCargos::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorCargos::class, 'eliminar'])->name('eliminar');
        
        // Generación masiva de cargos recurrentes
        Route::post('/generar-recurrentes', [ControladorCargos::class, 'generarCargosRecurrentes'])->name('generar-recurrentes');
    });
    
    // Pagos
    Route::prefix('pagos')->name('pagos.')->group(function () {
        Route::get('/', [ControladorPagos::class, 'index'])->name('index');
        Route::get('/pendientes-verificacion', [ControladorPagos::class, 'pendientesVerificacion'])->name('pendientes-verificacion');
        Route::get('/{id}', [ControladorPagos::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
        Route::post('/', [ControladorPagos::class, 'almacenar'])->name('almacenar');
        
        // Acciones de verificación
        Route::post('/{id}/verificar', [ControladorPagos::class, 'verificar'])->name('verificar');
        Route::post('/{id}/rechazar', [ControladorPagos::class, 'rechazar'])->name('rechazar');
        Route::post('/{id}/revertir', [ControladorPagos::class, 'revertir'])->name('revertir');
    });
});
