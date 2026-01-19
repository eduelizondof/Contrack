<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\InmuebleUsuario;
use App\Models\Ticket;
use App\Models\TicketTecnico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorTicketTecnicos extends Controller
{
    /**
     * Listar técnicos asignados a un ticket
     */
    public function index(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoTicket($request->user(), $ticket);

        $tecnicos = TicketTecnico::with(['usuario', 'asignadoPor'])
            ->where('ticket_id', $ticketId)
            ->orderByDesc('fecha_asignacion')
            ->get();

        return response()->json([
            'datos' => $tecnicos,
        ]);
    }

    /**
     * Asignar técnico a un ticket
     */
    public function asignar(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoInmueble($request->user(), $ticket->unidad->inmueble_id, 'administrador');

        $validados = $request->validate([
            'usuario_id' => 'required|exists:users,id',
        ]);

        // Verificar que el usuario sea técnico del inmueble
        $esTecnico = InmuebleUsuario::where('usuario_id', $validados['usuario_id'])
            ->where('inmueble_id', $ticket->unidad->inmueble_id)
            ->where('rol', 'tecnico')
            ->activos()
            ->exists();

        if (!$esTecnico) {
            return response()->json([
                'mensaje' => 'El usuario no es técnico de este inmueble',
            ], 422);
        }

        // Verificar si ya está asignado activamente
        $yaAsignado = TicketTecnico::where('ticket_id', $ticketId)
            ->where('usuario_id', $validados['usuario_id'])
            ->where('activo', true)
            ->exists();

        if ($yaAsignado) {
            return response()->json([
                'mensaje' => 'El técnico ya está asignado a este ticket',
            ], 422);
        }

        $asignacion = TicketTecnico::create([
            'ticket_id' => $ticketId,
            'usuario_id' => $validados['usuario_id'],
            'asignado_por' => $request->user()->id,
            'fecha_asignacion' => now(),
            'activo' => true,
        ]);

        // Actualizar fecha de asignación del ticket si es la primera
        if (!$ticket->fecha_asignacion) {
            $ticket->update(['fecha_asignacion' => now()]);
        }

        // Si el ticket está abierto, cambiarlo a en_proceso
        if ($ticket->estado === 'abierto') {
            $ticket->update(['estado' => 'en_proceso']);
        }

        return response()->json([
            'mensaje' => 'Técnico asignado exitosamente',
            'datos' => $asignacion->load(['usuario', 'asignadoPor']),
        ], 201);
    }

    /**
     * Desasignar técnico de un ticket
     */
    public function desasignar(Request $request, int $ticketId, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoInmueble($request->user(), $ticket->unidad->inmueble_id, 'administrador');

        $asignacion = TicketTecnico::where('ticket_id', $ticketId)
            ->where('id', $id)
            ->firstOrFail();

        $asignacion->update(['activo' => false]);

        return response()->json([
            'mensaje' => 'Técnico desasignado del ticket',
        ], 200);
    }

    /**
     * Obtener técnicos disponibles para un ticket
     */
    public function disponibles(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoInmueble($request->user(), $ticket->unidad->inmueble_id, 'administrador');

        // Técnicos del inmueble
        $tecnicos = InmuebleUsuario::with('usuario')
            ->where('inmueble_id', $ticket->unidad->inmueble_id)
            ->where('rol', 'tecnico')
            ->activos()
            ->get()
            ->map(fn ($asignacion) => [
                'id' => $asignacion->usuario->id,
                'nombre' => $asignacion->usuario->name,
                'email' => $asignacion->usuario->email,
            ]);

        // Marcar cuáles ya están asignados
        $asignadosIds = TicketTecnico::where('ticket_id', $ticketId)
            ->where('activo', true)
            ->pluck('usuario_id');

        $tecnicos = $tecnicos->map(function ($tecnico) use ($asignadosIds) {
            $tecnico['ya_asignado'] = $asignadosIds->contains($tecnico['id']);
            return $tecnico;
        });

        return response()->json([
            'datos' => $tecnicos->values(),
        ]);
    }

    /**
     * Verificar acceso al ticket
     */
    private function verificarAccesoTicket($usuario, $ticket): void
    {
        if ($ticket->usuario_reporta_id === $usuario->id) {
            return;
        }

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
