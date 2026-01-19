<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\InmuebleUsuario;
use App\Models\Ticket;
use App\Models\TicketComentario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorTicketComentarios extends Controller
{
    /**
     * Listar comentarios de un ticket
     */
    public function index(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $usuario = $request->user();
        $this->verificarAccesoTicket($usuario, $ticket);

        $query = TicketComentario::with('usuario')
            ->where('ticket_id', $ticketId);

        // Si no es admin/técnico, solo mostrar comentarios públicos
        $esAdminOTecnico = $this->esAdminOTecnico($usuario, $ticket);
        if (!$esAdminOTecnico) {
            $query->publicos();
        }

        $comentarios = $query->orderBy('creado_el')->get();

        return response()->json([
            'datos' => $comentarios,
        ]);
    }

    /**
     * Agregar comentario a un ticket
     */
    public function almacenar(Request $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $usuario = $request->user();
        $this->verificarAccesoTicket($usuario, $ticket);

        $validados = $request->validate([
            'comentario' => 'required|string',
            'interno' => 'nullable|boolean',
        ]);

        // Solo admins y técnicos pueden crear comentarios internos
        $esAdminOTecnico = $this->esAdminOTecnico($usuario, $ticket);
        if (!empty($validados['interno']) && !$esAdminOTecnico) {
            return response()->json([
                'mensaje' => 'No tienes permiso para crear comentarios internos',
            ], 403);
        }

        $comentario = TicketComentario::create([
            'ticket_id' => $ticketId,
            'usuario_id' => $usuario->id,
            'comentario' => $validados['comentario'],
            'interno' => $validados['interno'] ?? false,
        ]);

        return response()->json([
            'mensaje' => 'Comentario agregado exitosamente',
            'datos' => $comentario->load('usuario'),
        ], 201);
    }

    /**
     * Actualizar un comentario
     */
    public function actualizar(Request $request, int $ticketId, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $comentario = TicketComentario::where('ticket_id', $ticketId)->findOrFail($id);

        // Solo el autor puede editar su comentario
        if ($comentario->usuario_id !== $request->user()->id) {
            return response()->json([
                'mensaje' => 'Solo puedes editar tus propios comentarios',
            ], 403);
        }

        $validados = $request->validate([
            'comentario' => 'required|string',
        ]);

        $comentario->update($validados);

        return response()->json([
            'mensaje' => 'Comentario actualizado',
            'datos' => $comentario->fresh()->load('usuario'),
        ]);
    }

    /**
     * Eliminar un comentario
     */
    public function eliminar(Request $request, int $ticketId, int $id): JsonResponse
    {
        $ticket = Ticket::with('unidad')->findOrFail($ticketId);
        $comentario = TicketComentario::where('ticket_id', $ticketId)->findOrFail($id);
        $usuario = $request->user();

        // El autor o un admin pueden eliminar el comentario
        $esAdmin = $this->esAdmin($usuario, $ticket);
        if ($comentario->usuario_id !== $usuario->id && !$esAdmin) {
            return response()->json([
                'mensaje' => 'No tienes permiso para eliminar este comentario',
            ], 403);
        }

        $comentario->delete();

        return response()->json([
            'mensaje' => 'Comentario eliminado',
        ], 200);
    }

    /**
     * Verificar si el usuario es admin o técnico del inmueble
     */
    private function esAdminOTecnico($usuario, $ticket): bool
    {
        if ($usuario->hasRole('super-admin')) {
            return true;
        }

        return InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $ticket->unidad->inmueble_id)
            ->whereIn('rol', ['administrador', 'supervisor', 'tecnico'])
            ->activos()
            ->exists();
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
