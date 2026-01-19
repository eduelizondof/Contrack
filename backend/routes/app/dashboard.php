<?php

use App\Http\Controllers\Dashboard\ControladorDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Dashboard
|--------------------------------------------------------------------------
|
| Dashboard con vistas específicas por rol del usuario
|
*/

Route::prefix('dashboard')->middleware('auth:sanctum')->name('dashboard.')->group(function () {
    // Dashboard por rol
    Route::get('/inquilino', [ControladorDashboard::class, 'dashboardInquilino'])->name('inquilino');
    Route::get('/tecnico', [ControladorDashboard::class, 'dashboardTecnico'])->name('tecnico');
    Route::get('/administrador', [ControladorDashboard::class, 'dashboardAdministrador'])->name('administrador');
    
    // Estadísticas generales
    Route::get('/estadisticas', [ControladorDashboard::class, 'estadisticasGenerales'])->name('estadisticas');
});
