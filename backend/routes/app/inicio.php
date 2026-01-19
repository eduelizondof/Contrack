<?php

use App\Http\Controllers\Inicio\ControladorInicio;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Inicio
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas del módulo de inicio/dashboard
|
*/

Route::prefix('inicio')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ControladorInicio::class, 'index'])->name('app.inicio');
});
