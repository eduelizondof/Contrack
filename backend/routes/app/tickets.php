<?php

use App\Http\Controllers\Tickets\ControladorTicketAdjuntos;
use App\Http\Controllers\Tickets\ControladorTicketComentarios;
use App\Http\Controllers\Tickets\ControladorTicketTecnicos;
use App\Http\Controllers\Tickets\ControladorTickets;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Tickets
|--------------------------------------------------------------------------
|
| Sistema de tickets para incidencias y mantenimiento
|
*/

Route::prefix('tickets')->middleware('auth:sanctum')->name('tickets.')->group(function () {
    // CRUD principal de tickets
    Route::get('/', [ControladorTickets::class, 'index'])->name('index');
    Route::get('/{id}', [ControladorTickets::class, 'mostrar'])->name('mostrar')->where('id', '[0-9]+');
    Route::post('/', [ControladorTickets::class, 'almacenar'])->name('almacenar');
    Route::put('/{id}', [ControladorTickets::class, 'actualizar'])->name('actualizar');
    Route::delete('/{id}', [ControladorTickets::class, 'eliminar'])->name('eliminar');
    
    // Acciones especiales
    Route::post('/{id}/cambiar-estado', [ControladorTickets::class, 'cambiarEstado'])->name('cambiar-estado');
    Route::post('/{id}/valorar', [ControladorTickets::class, 'valorar'])->name('valorar');
    
    // Técnicos asignados
    Route::prefix('/{ticketId}/tecnicos')->name('tecnicos.')->group(function () {
        Route::get('/', [ControladorTicketTecnicos::class, 'index'])->name('index');
        Route::get('/disponibles', [ControladorTicketTecnicos::class, 'disponibles'])->name('disponibles');
        Route::post('/', [ControladorTicketTecnicos::class, 'asignar'])->name('asignar');
        Route::delete('/{id}', [ControladorTicketTecnicos::class, 'desasignar'])->name('desasignar');
    });
    
    // Comentarios
    Route::prefix('/{ticketId}/comentarios')->name('comentarios.')->group(function () {
        Route::get('/', [ControladorTicketComentarios::class, 'index'])->name('index');
        Route::post('/', [ControladorTicketComentarios::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorTicketComentarios::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorTicketComentarios::class, 'eliminar'])->name('eliminar');
    });
    
    // Adjuntos
    Route::prefix('/{ticketId}/adjuntos')->name('adjuntos.')->group(function () {
        Route::get('/', [ControladorTicketAdjuntos::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorTicketAdjuntos::class, 'mostrar'])->name('mostrar');
        Route::post('/', [ControladorTicketAdjuntos::class, 'almacenar'])->name('almacenar');
        Route::delete('/{id}', [ControladorTicketAdjuntos::class, 'eliminar'])->name('eliminar');
    });
});
