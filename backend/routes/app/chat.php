<?php

use App\Http\Controllers\Chat\ControladorConversacion;
use App\Http\Controllers\Chat\ControladorMensaje;
use App\Http\Controllers\Chat\ControladorMensajeAdjunto;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
    // Estado ligero del chat (para polling sin carga completa)
    Route::get('/estado', [ControladorConversacion::class, 'estado'])->name('chat.estado');
    
    // Conversaciones
    Route::get('/conversaciones', [ControladorConversacion::class, 'index'])->name('chat.conversaciones.index');
    Route::post('/conversaciones', [ControladorConversacion::class, 'store'])->name('chat.conversaciones.store');
    Route::get('/conversaciones/{conversacion}', [ControladorConversacion::class, 'show'])->name('chat.conversaciones.show');
    Route::delete('/conversaciones/{conversacion}', [ControladorConversacion::class, 'destroy'])->name('chat.conversaciones.destroy');
    Route::post('/conversaciones/{conversacion}/archivar', [ControladorConversacion::class, 'archivar'])->name('chat.conversaciones.archivar');
    Route::post('/conversaciones/{conversacion}/desarchivar', [ControladorConversacion::class, 'desarchivar'])->name('chat.conversaciones.desarchivar');
    Route::post('/conversaciones/{conversacion}/salir', [ControladorConversacion::class, 'salir'])->name('chat.conversaciones.salir');
    Route::post('/conversaciones/{conversacion}/miembros', [ControladorConversacion::class, 'agregarMiembro'])->name('chat.miembros.store');
    Route::delete('/conversaciones/{conversacion}/miembros/{userId}', [ControladorConversacion::class, 'removerMiembro'])->name('chat.miembros.destroy');
    Route::put('/conversaciones/{conversacion}/admin', [ControladorConversacion::class, 'actualizarAdmin'])->name('chat.admin.update');
    
    // Búsqueda de usuarios
    Route::get('/usuarios/buscar', [ControladorConversacion::class, 'buscarUsuarios'])->name('chat.usuarios.buscar');
    
    // Mensajes
    Route::get('/conversaciones/{conversacion}/mensajes', [ControladorMensaje::class, 'index'])->name('chat.mensajes.index');
    Route::post('/conversaciones/{conversacion}/mensajes', [ControladorMensaje::class, 'store'])->name('chat.mensajes.store');
    Route::get('/conversaciones/{conversacion}/mensajes/polling', [ControladorMensaje::class, 'polling'])->name('chat.mensajes.polling');
    Route::get('/conversaciones/{conversacion}/mensajes/buscar', [ControladorMensaje::class, 'buscar'])->name('chat.mensajes.buscar');
    Route::put('/mensajes/{mensaje}', [ControladorMensaje::class, 'update'])->name('chat.mensajes.update');
    Route::delete('/mensajes/{mensaje}', [ControladorMensaje::class, 'destroy'])->name('chat.mensajes.destroy');
    Route::post('/conversaciones/{conversacion}/visto', [ControladorMensaje::class, 'marcarVisto'])->name('chat.mensajes.visto');
    
    // Adjuntos
    Route::post('/conversaciones/{conversacion}/adjuntos', [ControladorMensajeAdjunto::class, 'store'])->name('chat.adjuntos.store');
    Route::delete('/adjuntos/{adjunto}', [ControladorMensajeAdjunto::class, 'destroy'])->name('chat.adjuntos.destroy');
});
