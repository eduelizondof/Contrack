<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SolicitudCrearConversacion;
use App\Http\Resources\Chat\RecursoConversacion;
use App\Http\Resources\Chat\RecursoUsuarioChat;
use App\Models\Chat\Conversacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorConversacion extends Controller
{
    /**
     * Lista de conversaciones del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $archivadas = $request->boolean('archivadas', false);

        $query = Conversacion::query()
            ->with(['ultimoMensaje.usuario', 'usuarios' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email');
            }, 'creador:id,name']);

        if ($archivadas) {
            $query->archivadasPorUsuario($user->id);
        } else {
            $query->activasPorUsuario($user->id);
        }

        $conversaciones = $query
            ->ordenarPorUltimoMensaje()
            ->get();

        return response()->json([
            'datos' => RecursoConversacion::collection($conversaciones),
        ]);
    }

    /**
     * Detalle de una conversación
     */
    public function show(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar que el usuario pertenece a la conversación
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $conversacion->load(['usuarios' => function ($q) {
            $q->select('users.id', 'users.name', 'users.email');
        }, 'creador:id,name']);

        return response()->json([
            'datos' => new RecursoConversacion($conversacion),
        ]);
    }

    /**
     * Eliminar una conversación (solo para el creador)
     */
    public function destroy(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar que el usuario es el creador
        if ($conversacion->creado_por !== $user->id) {
            return response()->json([
                'mensaje' => 'Solo el creador puede eliminar esta conversación',
            ], 403);
        }

        $conversacion->delete(); // Soft delete por BaseModel

        return response()->json([
            'mensaje' => 'Conversación eliminada exitosamente',
        ]);
    }

    /**
     * Crear una nueva conversación
     */
    public function store(SolicitudCrearConversacion $request): JsonResponse
    {
        $user = $request->user();
        $usuariosIds = collect($request->usuarios)->push($user->id)->unique()->values();

        // Mínimo 2 participantes
        if ($usuariosIds->count() < 2) {
            return response()->json([
                'mensaje' => 'La conversación debe tener al menos 2 participantes',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generar nombre automáticamente si no se proporciona
            $nombre = $request->nombre;
            if (empty($nombre)) {
                // Obtener los usuarios (excluyendo al creador)
                $otrosUsuarios = User::whereIn('id', $usuariosIds)
                    ->where('id', '!=', $user->id)
                    ->limit(2)
                    ->pluck('name')
                    ->toArray();
                
                if (count($otrosUsuarios) > 0) {
                    $nombre = implode(', ', $otrosUsuarios);
                } else {
                    // Fallback: usar el nombre del creador (caso raro)
                    $nombre = $user->name;
                }
            }

            $conversacion = Conversacion::create([
                'nombre' => $nombre,
                'es_grupo' => $usuariosIds->count() > 2 || $request->filled('nombre'),
                'creado_por' => $user->id,
            ]);

            // Agregar participantes
            foreach ($usuariosIds as $userId) {
                $conversacion->usuarios()->attach($userId, [
                    'es_admin' => $userId === $user->id, // El creador es admin
                ]);
            }

            DB::commit();

            $conversacion->load(['usuarios' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email');
            }, 'creador:id,name']);

            return response()->json([
                'mensaje' => 'Conversación creada exitosamente',
                'datos' => new RecursoConversacion($conversacion),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'mensaje' => 'Error al crear la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archivar conversación para el usuario
     */
    public function archivar(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $conversacion->usuarios()->updateExistingPivot($user->id, [
            'archivada' => true,
        ]);

        return response()->json([
            'mensaje' => 'Conversación archivada',
        ]);
    }

    /**
     * Desarchivar conversación para el usuario
     */
    public function desarchivar(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $conversacion->usuarios()->updateExistingPivot($user->id, [
            'archivada' => false,
        ]);

        return response()->json([
            'mensaje' => 'Conversación desarchivada',
        ]);
    }

    /**
     * Salir de una conversación
     */
    public function salir(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $totalMiembros = $conversacion->usuarios()->count();

        // Si es el único miembro, eliminar el grupo
        if ($totalMiembros === 1) {
            $conversacion->delete(); // Soft delete por BaseModel

            return response()->json([
                'mensaje' => 'Grupo eliminado',
                'grupo_eliminado' => true,
            ]);
        }

        // Si hay solo 2 miembros, no se puede salir
        if ($totalMiembros === 2) {
            return response()->json([
                'mensaje' => 'No puedes salir de una conversación con solo 2 participantes',
            ], 422);
        }

        // Si es el único admin, asignar admin a otro usuario
        if ($conversacion->esAdmin($user->id)) {
            $otroUsuario = $conversacion->usuarios()
                ->where('user_id', '!=', $user->id)
                ->first();
            
            if ($otroUsuario) {
                $conversacion->usuarios()->updateExistingPivot($otroUsuario->id, [
                    'es_admin' => true,
                ]);
            }
        }

        $conversacion->usuarios()->detach($user->id);

        return response()->json([
            'mensaje' => 'Has salido de la conversación',
            'grupo_eliminado' => false,
        ]);
    }

    /**
     * Agregar miembro a la conversación (solo admin)
     */
    public function agregarMiembro(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        if (!$conversacion->esAdmin($user->id)) {
            return response()->json([
                'mensaje' => 'Solo los administradores pueden agregar miembros',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if ($conversacion->perteneceUsuario($request->user_id)) {
            return response()->json([
                'mensaje' => 'El usuario ya es miembro de esta conversación',
            ], 422);
        }

        $conversacion->usuarios()->attach($request->user_id, [
            'es_admin' => false,
        ]);

        $nuevoMiembro = User::find($request->user_id);

        return response()->json([
            'mensaje' => 'Miembro agregado exitosamente',
            'datos' => new RecursoUsuarioChat($nuevoMiembro),
        ]);
    }

    /**
     * Remover miembro de la conversación (solo admin)
     */
    public function removerMiembro(Request $request, Conversacion $conversacion, int $userId): JsonResponse
    {
        $user = $request->user();

        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        if (!$conversacion->esAdmin($user->id)) {
            return response()->json([
                'mensaje' => 'Solo los administradores pueden remover miembros',
            ], 403);
        }

        if (!$conversacion->perteneceUsuario($userId)) {
            return response()->json([
                'mensaje' => 'El usuario no es miembro de esta conversación',
            ], 422);
        }

        // No se puede remover al creador
        if ($conversacion->creado_por === $userId) {
            return response()->json([
                'mensaje' => 'No puedes remover al creador de la conversación',
            ], 422);
        }

        // Mínimo 2 participantes
        if ($conversacion->usuarios()->count() <= 2) {
            return response()->json([
                'mensaje' => 'La conversación debe tener al menos 2 participantes',
            ], 422);
        }

        $conversacion->usuarios()->detach($userId);

        return response()->json([
            'mensaje' => 'Miembro removido exitosamente',
        ]);
    }

    /**
     * Actualizar rol de admin
     */
    public function actualizarAdmin(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        if (!$conversacion->esAdmin($user->id)) {
            return response()->json([
                'mensaje' => 'Solo los administradores pueden cambiar roles',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'es_admin' => 'required|boolean',
        ]);

        if (!$conversacion->perteneceUsuario($request->user_id)) {
            return response()->json([
                'mensaje' => 'El usuario no es miembro de esta conversación',
            ], 422);
        }

        $conversacion->usuarios()->updateExistingPivot($request->user_id, [
            'es_admin' => $request->es_admin,
        ]);

        return response()->json([
            'mensaje' => 'Rol actualizado exitosamente',
        ]);
    }

    /**
     * Obtener estado ligero del chat (para polling sin carga completa)
     */
    public function estado(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Obtener conversaciones activas del usuario
        $conversaciones = Conversacion::activasPorUsuario($user->id)->get();
        
        // Calcular total de mensajes no leídos
        $totalNoLeidos = $conversaciones->sum(fn($c) => $c->mensajesNoLeidos($user->id));
        
        return response()->json([
            'total_no_leidos' => $totalNoLeidos,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Buscar usuarios para agregar a conversación
     */
    public function buscarUsuarios(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $usuarios = User::where('activo', true)
            ->where('id', '!=', $request->user()->id)
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->q}%")
                    ->orWhere('email', 'like', "%{$request->q}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'datos' => $usuarios,
        ]);
    }
}
