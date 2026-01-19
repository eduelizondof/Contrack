<?php

namespace App\Http\Controllers\Inicio;

use App\Http\Controllers\Controller;
use App\Http\Resources\Autenticacion\RecursoUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorInicio extends Controller
{
    /**
     * Obtener información del dashboard/inicio
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'mensaje' => 'Bienvenido al sistema',
            'usuario' => new RecursoUsuario($usuario),
            'datos' => [
                'fecha_actual' => now()->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
