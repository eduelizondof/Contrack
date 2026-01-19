<?php

use App\Http\Controllers\Notificaciones\ControladorNotificaciones;
use Illuminate\Support\Facades\Route;

Route::prefix('notificaciones')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ControladorNotificaciones::class, 'index'])
        ->name('notificaciones.index');
    
    Route::get('/no-leidas', [ControladorNotificaciones::class, 'noLeidas'])
        ->name('notificaciones.no-leidas');
    
    Route::get('/contar', [ControladorNotificaciones::class, 'contarNoLeidas'])
        ->name('notificaciones.contar');
    
    Route::post('/{id}/leer', [ControladorNotificaciones::class, 'marcarComoLeida'])
        ->name('notificaciones.leer');
    
    Route::post('/leer-todas', [ControladorNotificaciones::class, 'marcarTodasComoLeidas'])
        ->name('notificaciones.leer-todas');
    
    Route::delete('/{id}', [ControladorNotificaciones::class, 'eliminar'])
        ->name('notificaciones.eliminar');
});
