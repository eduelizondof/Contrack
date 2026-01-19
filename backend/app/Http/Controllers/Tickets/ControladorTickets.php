<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Ticket;
use App\Models\TicketTecnico;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorTickets extends Controller
{
    /**
     * Listar tickets
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Ticket::with(['unidad.inmueble', 'usuarioReporta', 'tecnicos.usuario']);

        // Filtrar según rol del usuario
        if ($request->has('mis_tickets')) {
            // Tickets reportados por el usuario
            $query->where('usuario_reporta_id', $usuario->id);
        } elseif ($request->has('asignados')) {
            // Tickets asignados al usuario (técnico)
            $query->whereHas('tecnicos', fn ($q) => $q->where('usuario_id', $usuario->id)->where('activo', true));
        } else {
            // Tickets de inmuebles accesibles
            $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);
            $query->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds));
        }

        // Filtros
        if ($request->filled('unidad_id')) {
            $query->where('unidad_id', $request->unidad_id);
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('activos')) {
            $query->activos();
        }

        // Paginación
        $porPagina = $request->input('por_pagina', 15);
        $tickets = $query->orderByDesc('fecha_reporte')->paginate($porPagina);

        return response()->json([
            'datos' => $tickets->items(),
            'meta' => [
                'total' => $tickets->total(),
                'por_pagina' => $tickets->perPage(),
                'pagina_actual' => $tickets->currentPage(),
                'ultima_pagina' => $tickets->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar un ticket específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::with([
            'unidad.inmueble',
            'usuarioReporta',
            'tecnicos.usuario',
            'comentarios.usuario',
            'adjuntos.usuario',
        ])->findOrFail($id);

        $this->verificarAccesoTicket($request->user(), $ticket);

        // Info adicional
        $ticket->esta_asignado = $ticket->esta_asignado;
        $ticket->tecnico_activo = $ticket->tecnico_activo;
        $ticket->tiempo_respuesta = $ticket->tiempo_respuesta;

        return response()->json([
            'datos' => $ticket,
        ]);
    }

    /**
     * Crear un nuevo ticket
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'categoria' => 'required|string|in:plomeria,electricidad,limpieza,seguridad,estructura,otro',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'prioridad' => 'nullable|string|in:baja,media,alta,urgente',
        ]);

        $unidad = Unidad::findOrFail($validados['unidad_id']);
        $usuario = $request->user();

        // Verificar que el usuario tenga relación con la unidad o inmueble
        $tieneAcceso = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $unidad->inmueble_id)
            ->activos()
            ->exists();

        if (!$tieneAcceso && !$usuario->hasRole('super-admin')) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta unidad para reportar tickets',
            ], 403);
        }

        $ticket = Ticket::create([
            'unidad_id' => $validados['unidad_id'],
            'usuario_reporta_id' => $usuario->id,
            'categoria' => $validados['categoria'],
            'titulo' => $validados['titulo'],
            'descripcion' => $validados['descripcion'],
            'prioridad' => $validados['prioridad'] ?? 'media',
            'estado' => 'abierto',
            'fecha_reporte' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Ticket creado exitosamente',
            'datos' => $ticket->load(['unidad.inmueble', 'usuarioReporta']),
        ], 201);
    }

    /**
     * Actualizar un ticket
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $ticket->unidad->inmueble_id, 'administrador');

        $validados = $request->validate([
            'categoria' => 'sometimes|string|in:plomeria,electricidad,limpieza,seguridad,estructura,otro',
            'titulo' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|string',
            'prioridad' => 'sometimes|string|in:baja,media,alta,urgente',
        ]);

        $ticket->update($validados);

        return response()->json([
            'mensaje' => 'Ticket actualizado exitosamente',
            'datos' => $ticket->fresh()->load(['unidad.inmueble', 'usuarioReporta', 'tecnicos.usuario']),
        ]);
    }

    /**
     * Eliminar un ticket
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $ticket->unidad->inmueble_id, 'administrador');

        $ticket->delete();

        return response()->json([
            'mensaje' => 'Ticket eliminado exitosamente',
        ], 200);
    }

    /**
     * Cambiar estado del ticket
     */
    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($id);
        $usuario = $request->user();

        // Técnicos asignados pueden cambiar estado
        $esTecnicoAsignado = $ticket->tecnicos()
            ->where('usuario_id', $usuario->id)
            ->where('activo', true)
            ->exists();

        if (!$esTecnicoAsignado) {
            $this->verificarAccesoInmueble($usuario, $ticket->unidad->inmueble_id, 'administrador');
        }

        $validados = $request->validate([
            'estado' => 'required|string|in:abierto,en_proceso,pausado,resuelto,cerrado,cancelado',
        ]);

        $estadoAnterior = $ticket->estado;
        $nuevoEstado = $validados['estado'];

        // Actualizar fechas según el cambio de estado
        $actualizaciones = ['estado' => $nuevoEstado];

        if ($nuevoEstado === 'resuelto' && !$ticket->fecha_resolucion) {
            $actualizaciones['fecha_resolucion'] = now();
        }

        $ticket->update($actualizaciones);

        return response()->json([
            'mensaje' => "Estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'",
            'datos' => $ticket->fresh()->load(['unidad.inmueble', 'usuarioReporta', 'tecnicos.usuario']),
        ]);
    }

    /**
     * Valorar un ticket (solo el usuario que lo reportó)
     */
    public function valorar(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        // Solo el usuario que reportó puede valorar
        if ($ticket->usuario_reporta_id !== $request->user()->id) {
            return response()->json([
                'mensaje' => 'Solo el usuario que reportó el ticket puede valorarlo',
            ], 403);
        }

        // Solo se puede valorar tickets resueltos o cerrados
        if (!in_array($ticket->estado, ['resuelto', 'cerrado'])) {
            return response()->json([
                'mensaje' => 'Solo se pueden valorar tickets resueltos o cerrados',
            ], 422);
        }

        $validados = $request->validate([
            'valoracion' => 'required|integer|min:1|max:5',
            'comentario_valoracion' => 'nullable|string|max:500',
        ]);

        $ticket->update($validados);

        return response()->json([
            'mensaje' => 'Valoración registrada exitosamente',
            'datos' => $ticket->fresh(),
        ]);
    }

    /**
     * Obtener IDs de inmuebles accesibles
     */
    private function obtenerInmueblesAccesibles($usuario): \Illuminate\Support\Collection
    {
        if ($usuario->hasRole('super-admin')) {
            return Inmueble::pluck('id');
        }

        return InmuebleUsuario::where('usuario_id', $usuario->id)
            ->activos()
            ->pluck('inmueble_id');
    }

    /**
     * Verificar acceso al ticket
     */
    private function verificarAccesoTicket($usuario, $ticket): void
    {
        // El usuario que reportó puede ver su ticket
        if ($ticket->usuario_reporta_id === $usuario->id) {
            return;
        }

        // Técnicos asignados pueden ver el ticket
        $esTecnico = $ticket->tecnicos()
            ->where('usuario_id', $usuario->id)
            ->exists();

        if ($esTecnico) {
            return;
        }

        $this->verificarAccesoInmueble($usuario, $ticket->unidad->inmueble_id);
    }

    /**
     * Verificar acceso al inmueble
     */
    private function verificarAccesoInmueble($usuario, int $inmuebleId, ?string $rolMinimo = null): void
    {
        if ($usuario->hasRole('super-admin')) {
            return;
        }

        $query = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $inmuebleId)
            ->activos();

        if ($rolMinimo === 'administrador') {
            $query->whereIn('rol', ['administrador', 'supervisor']);
        }

        if (!$query->exists()) {
            abort(403, 'No tienes acceso a este inmueble');
        }
    }
}
