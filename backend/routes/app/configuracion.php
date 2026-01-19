<?php

use App\Http\Controllers\Configuracion\ControladorRoles;
use App\Http\Controllers\Configuracion\ControladorUsuarios;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo de Configuración
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas del módulo de configuración del sistema
| Incluye gestión de roles, permisos y usuarios
|
*/

Route::prefix('configuracion')->middleware('auth:sanctum')->group(function () {
    // Rutas de roles - Requieren permiso de configuración
    Route::prefix('roles')->name('configuracion.roles.')->group(function () {
        Route::get('/', [ControladorRoles::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorRoles::class, 'mostrar'])->name('mostrar');
        Route::post('/', [ControladorRoles::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorRoles::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorRoles::class, 'eliminar'])->name('eliminar');
        Route::post('/{id}/permisos', [ControladorRoles::class, 'asignarPermisos'])->name('asignar-permisos');
    });

    // Rutas de usuarios
    Route::prefix('usuarios')->name('configuracion.usuarios.')->group(function () {
        Route::get('/', [ControladorUsuarios::class, 'index'])->name('index');
        Route::get('/{id}', [ControladorUsuarios::class, 'mostrar'])->name('mostrar');
        Route::post('/', [ControladorUsuarios::class, 'almacenar'])->name('almacenar');
        Route::put('/{id}', [ControladorUsuarios::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}', [ControladorUsuarios::class, 'eliminar'])->name('eliminar');
        Route::put('/{id}/rol', [ControladorUsuarios::class, 'actualizarRol'])->name('actualizar-rol');
        Route::put('/{id}/password', [ControladorUsuarios::class, 'restablecerPassword'])->name('restablecer-password');
    });

    // Ruta para obtener permisos por categorías
    Route::get('/permisos/categorias', [ControladorRoles::class, 'obtenerPermisosCategorias'])->name('configuracion.permisos.categorias');

   
});
