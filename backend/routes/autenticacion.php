<?php

use App\Http\Controllers\Autenticacion\ControladorAutenticacion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas relacionadas con la autenticación:
| - Login
| - Logout
|
*/

// Rutas públicas de autenticación
Route::post('/login', [ControladorAutenticacion::class, 'login'])->name('autenticacion.login');

// Ruta protegida de logout
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ControladorAutenticacion::class, 'logout'])->name('autenticacion.logout');
});
