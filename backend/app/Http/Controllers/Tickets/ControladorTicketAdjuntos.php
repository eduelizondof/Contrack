<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\InmuebleUsuario;
use App\Models\Ticket;
use App\Models\TicketAdjunto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorTicketAdjuntos extends Controller
{
    /**
     * Listar adjuntos de un ticket
     */
    public function index(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoTicket($request->user(), $ticket);

        $adjuntos = TicketAdjunto::with('usuario')
            ->where('ticket_id', $ticketId)
            ->orderByDesc('creado_el')
            ->get();

        return response()->json([
            'datos' => $adjuntos,
        ]);
    }

    /**
     * Agregar adjunto a un ticket
     */
    public function almacenar(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoTicket($request->user(), $ticket);

        $validados = $request->validate([
            'nombre_archivo' => 'required|string|max:255',
            'ruta_archivo' => 'required|string',
            'tipo_archivo' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $adjunto = TicketAdjunto::create([
            'ticket_id' => $ticketId,
            'usuario_id' => $request->user()->id,
            'nombre_archivo' => $validados['nombre_archivo'],
            'ruta_archivo' => $validados['ruta_archivo'],
            'tipo_archivo' => $validados['tipo_archivo'],
            'descripcion' => $validados['descripcion'],
        ]);

        return response()->json([
            'mensaje' => 'Archivo adjuntado exitosamente',
            'datos' => $adjunto->load('usuario'),
        ], 201);
    }

    /**
     * Mostrar un adjunto específico
     */
    public function mostrar(Request $request, int $ticketId, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $this->verificarAccesoTicket($request->user(), $ticket);

        $adjunto = TicketAdjunto::where('ticket_id', $ticketId)
            ->with('usuario')
            ->findOrFail($id);

        return response()->json([
            'datos' => $adjunto,
        ]);
    }

    /**
     * Eliminar un adjunto
     */
    public function eliminar(Request $request, int $ticketId, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $adjunto = TicketAdjunto::where('ticket_id', $ticketId)->findOrFail($id);
        $usuario = $request->user();

        // El usuario que subió el archivo o un admin puede eliminarlo
        $esAdmin = $this->esAdmin($usuario, $ticket);
        if ($adjunto->usuario_id !== $usuario->id && !$esAdmin) {
            return response()->json([
                'mensaje' => 'No tienes permiso para eliminar este archivo',
            ], 403);
        }

        // TODO: Eliminar archivo físico del storage si es necesario

        $adjunto->delete();

        return response()->json([
            'mensaje' => 'Archivo eliminado exitosamente',
        ], 200);
    }

    /**
     * Verificar si el usuario es admin del inmueble
     */
    private function esAdmin($usuario, $ticket): bool
    {
        if ($usuario->hasRole('super-admin')) {
            return true;
        }

        return InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $ticket->unidad->inmueble_id)
            ->whereIn('rol', ['administrador', 'supervisor'])
            ->activos()
            ->exists();
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

        if ($usuario->hasRole('super-admin')) {
            return;
        }

        $tieneAcceso = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $ticket->unidad->inmueble_id)
            ->activos()
            ->exists();

        if (!$tieneAcceso) {
            abort(403, 'No tienes acceso a este ticket');
        }
    }
}
