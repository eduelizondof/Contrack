<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas principales de la API. Las rutas específicas de módulos
| se encuentran en sus respectivos archivos dentro de routes/
|
*/

// Ruta para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'usuario' => new \App\Http\Resources\Autenticacion\RecursoUsuario($request->user()),
    ]);
});

// Cargar rutas de módulos
require __DIR__ . '/autenticacion.php';
require __DIR__ . '/app/inicio.php';
require __DIR__ . '/app/configuracion.php';
require __DIR__ . '/app/notificaciones.php';
require __DIR__ . '/app/chat.php';
