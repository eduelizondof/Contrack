<?php

use App\Http\Controllers\Contratos\ControladorContratos;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Contratos
|--------------------------------------------------------------------------
|
| Gestión de contratos de renta y propiedad
|
*/

Route::prefix('contratos')->middleware('auth:sanctum')->name('contratos.')->group(function () {
    // CRUD de contratos
    Route::get('/', [ControladorContratos::class, 'index'])->name('index');
    Route::get('/activos', [ControladorContratos::class, 'activos'])->name('activos');
    Route::get('/por-vencer', [ControladorContratos::class, 'porVencer'])->name('por-vencer');
    Route::get('/{id}', [ControladorContratos::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
    Route::post('/', [ControladorContratos::class, 'almacenar'])->name('almacenar');
    Route::put('/{id}', [ControladorContratos::class, 'actualizar'])->name('actualizar');
    Route::delete('/{id}', [ControladorContratos::class, 'eliminar'])->name('eliminar');
});
