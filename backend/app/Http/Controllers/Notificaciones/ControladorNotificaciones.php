<?php

namespace App\Http\Controllers\Notificaciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notificaciones\RecursoNotificacion;
use App\Services\Notificaciones\ServicioNotificaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorNotificaciones extends Controller
{
    protected ServicioNotificaciones $servicio;

    public function __construct(ServicioNotificaciones $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * Listar notificaciones del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $filtros = [];

        if ($request->has('leidas')) {
            $filtros['leidas'] = filter_var($request->leidas, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('prioridad')) {
            $filtros['prioridad'] = $request->prioridad;
        }

        if ($request->has('categoria')) {
            $filtros['categoria'] = $request->categoria;
        }

        $porPagina = $request->get('por_pagina', 15);
        $notificaciones = $this->servicio->obtenerPaginadas($usuario->id, $filtros, $porPagina);

        return response()->json([
            'datos' => RecursoNotificacion::collection($notificaciones->items()),
            'meta' => [
                'total' => $notificaciones->total(),
                'por_pagina' => $notificaciones->perPage(),
                'pagina_actual' => $notificaciones->currentPage(),
                'ultima_pagina' => $notificaciones->lastPage(),
            ],
        ]);
    }

    /**
     * Obtener solo notificaciones no leídas
     */
    public function noLeidas(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $limite = $request->get('limite', 20);

        $notificaciones = $this->servicio->obtenerNoLeidas($usuario->id, $limite);

        return response()->json([
            'datos' => RecursoNotificacion::collection($notificaciones),
        ]);
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $contador = $this->servicio->contarNoLeidas($usuario->id);

        return response()->json([
            'contador' => $contador,
        ]);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $marcada = $this->servicio->marcarComoLeida($usuario->id, $id);

        if ($marcada) {
            return response()->json([
                'mensaje' => 'Notificación marcada como leída',
            ]);
        }

        return response()->json([
            'mensaje' => 'Notificación no encontrada o ya estaba leída',
        ], 404);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasComoLeidas(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $marcadas = $this->servicio->marcarTodasComoLeidas($usuario->id);

        return response()->json([
            'mensaje' => "Se marcaron {$marcadas} notificaciones como leídas",
            'marcadas' => $marcadas,
        ]);
    }

    /**
     * Eliminar notificación para el usuario
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $eliminada = $this->servicio->eliminar($usuario->id, $id);

        if ($eliminada) {
            return response()->json([
                'mensaje' => 'Notificación eliminada',
            ]);
        }

        return response()->json([
            'mensaje' => 'Notificación no encontrada',
        ], 404);
    }
}
