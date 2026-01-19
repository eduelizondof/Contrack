<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SolicitudActualizarMensaje;
use App\Http\Requests\Chat\SolicitudCrearMensaje;
use App\Http\Resources\Chat\RecursoMensaje;
use App\Models\Chat\Conversacion;
use App\Models\Chat\Mensaje;
use App\Models\Chat\MensajeVisto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorMensaje extends Controller
{
    /**
     * Lista de mensajes de una conversación (paginados)
     */
    public function index(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar acceso
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $perPage = $request->integer('per_page', 10);
        $antes = $request->integer('antes'); // Para scroll infinito hacia arriba

        // Cargar relación de usuarios de la conversación para calcular visto_por_todos
        $conversacion->load('usuarios:id');

        $query = Mensaje::with([
            'usuario:id,name,email',
            'adjuntos',
            'respondeA.usuario:id,name',
            'vistos.usuario:id,name'
        ])
            ->porConversacion($conversacion->id)
            ->orderByDesc('creado_el');

        if ($antes) {
            $query->antesDe($antes);
        }

        $mensajes = $query->limit($perPage)->get();

        // Cargar conversación en cada mensaje para calcular visto_por_todos
        foreach ($mensajes as $mensaje) {
            $mensaje->setRelation('conversacion', $conversacion);
        }

        // Marcar mensajes como vistos automáticamente
        $this->marcarComoVistos($conversacion, $user->id);

        return response()->json([
            'datos' => RecursoMensaje::collection($mensajes->reverse()->values()),
            'tiene_mas' => $mensajes->count() === $perPage,
        ]);
    }

    /**
     * Polling para nuevos mensajes
     */
    public function polling(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar acceso
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $despues = $request->integer('despues'); // ID del último mensaje recibido

        if (!$despues) {
            return response()->json([
                'datos' => [],
                'nuevos' => 0,
            ]);
        }

        // Cargar relación de usuarios de la conversación para calcular visto_por_todos
        $conversacion->load('usuarios:id');

        $mensajes = Mensaje::with([
            'usuario:id,name,email',
            'adjuntos',
            'respondeA.usuario:id,name',
            'vistos.usuario:id,name'
        ])
            ->porConversacion($conversacion->id)
            ->despuesDe($despues)
            ->orderBy('creado_el')
            ->get();

        // Cargar conversación en cada mensaje para calcular visto_por_todos
        foreach ($mensajes as $mensaje) {
            $mensaje->setRelation('conversacion', $conversacion);
        }

        // Marcar como vistos si hay nuevos
        if ($mensajes->isNotEmpty()) {
            $this->marcarComoVistos($conversacion, $user->id);
        }

        return response()->json([
            'datos' => RecursoMensaje::collection($mensajes),
            'nuevos' => $mensajes->count(),
        ]);
    }

    /**
     * Enviar un nuevo mensaje
     */
    public function store(SolicitudCrearMensaje $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar acceso
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        // Validar que el mensaje al que responde pertenece a la conversación
        if ($request->responde_a_id) {
            $mensajeRespuesta = Mensaje::find($request->responde_a_id);
            if (!$mensajeRespuesta || $mensajeRespuesta->conversacion_id !== $conversacion->id) {
                return response()->json([
                    'mensaje' => 'El mensaje de respuesta no es válido',
                ], 422);
            }
        }

        // Sanitizar contenido HTML
        $contenido = $this->sanitizarContenido($request->contenido);

        $mensaje = Mensaje::create([
            'conversacion_id' => $conversacion->id,
            'user_id' => $user->id,
            'responde_a_id' => $request->responde_a_id,
            'tipo' => $request->tipo ?? 'texto',
            'contenido' => $contenido,
        ]);

        // Cargar relación de usuarios de la conversación para calcular visto_por_todos
        $conversacion->load('usuarios:id');

        $mensaje->load([
            'usuario:id,name,email',
            'adjuntos',
            'respondeA.usuario:id,name',
            'vistos.usuario:id,name'
        ]);
        
        // Cargar conversación en el mensaje para calcular visto_por_todos
        $mensaje->setRelation('conversacion', $conversacion);

        // Actualizar último visto del usuario
        $conversacion->usuarios()->updateExistingPivot($user->id, [
            'ultimo_visto_at' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Mensaje enviado',
            'datos' => new RecursoMensaje($mensaje),
        ], 201);
    }

    /**
     * Editar un mensaje
     */
    public function update(SolicitudActualizarMensaje $request, Mensaje $mensaje): JsonResponse
    {
        $user = $request->user();

        // Verificar que es el autor
        if (!$mensaje->puedeEditar($user->id)) {
            return response()->json([
                'mensaje' => 'No puedes editar este mensaje',
            ], 403);
        }

        // Sanitizar contenido HTML
        $contenido = $this->sanitizarContenido($request->contenido);

        $mensaje->update([
            'contenido' => $contenido,
            'editado' => true,
        ]);

        // Cargar relación de usuarios de la conversación para calcular visto_por_todos
        $conversacion = $mensaje->conversacion;
        $conversacion->load('usuarios:id');

        $mensaje->load([
            'usuario:id,name,email',
            'adjuntos',
            'respondeA.usuario:id,name',
            'vistos.usuario:id,name'
        ]);
        
        // Cargar conversación en el mensaje para calcular visto_por_todos
        $mensaje->setRelation('conversacion', $conversacion);

        return response()->json([
            'mensaje' => 'Mensaje editado',
            'datos' => new RecursoMensaje($mensaje),
        ]);
    }

    /**
     * Eliminar un mensaje (soft delete)
     */
    public function destroy(Request $request, Mensaje $mensaje): JsonResponse
    {
        $user = $request->user();

        // Verificar que es el autor
        if (!$mensaje->puedeEliminar($user->id)) {
            return response()->json([
                'mensaje' => 'No puedes eliminar este mensaje',
            ], 403);
        }

        $mensaje->update([
            'eliminado' => true,
        ]);

        return response()->json([
            'mensaje' => 'Mensaje eliminado',
        ]);
    }

    /**
     * Marcar mensajes como vistos
     */
    public function marcarVisto(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar acceso
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $this->marcarComoVistos($conversacion, $user->id);

        return response()->json([
            'mensaje' => 'Mensajes marcados como vistos',
        ]);
    }

    /**
     * Buscar mensajes en una conversación
     */
    public function buscar(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar acceso
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $mensajes = Mensaje::with(['usuario:id,name'])
            ->porConversacion($conversacion->id)
            ->noEliminados()
            ->where('contenido', 'like', "%{$request->q}%")
            ->orderByDesc('creado_el')
            ->limit(20)
            ->get();

        return response()->json([
            'datos' => $mensajes->map(fn($m) => [
                'id' => $m->id,
                'contenido' => $m->preview,
                'usuario' => $m->usuario->name,
                'creado_el' => $m->creado_el?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Helper: Marcar mensajes como vistos
     */
    private function marcarComoVistos(Conversacion $conversacion, int $userId): void
    {
        // Actualizar ultimo_visto_at en el pivot
        $conversacion->usuarios()->updateExistingPivot($userId, [
            'ultimo_visto_at' => now(),
        ]);

        // Obtener mensajes no vistos de otros usuarios
        $mensajesNoVistos = Mensaje::porConversacion($conversacion->id)
            ->noEliminados()
            ->where('user_id', '!=', $userId)
            ->whereDoesntHave('vistos', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->pluck('id');

        // Crear registros de visto
        if ($mensajesNoVistos->isNotEmpty()) {
            $registros = $mensajesNoVistos->map(fn($id) => [
                'mensaje_id' => $id,
                'user_id' => $userId,
                'creado_el' => now(),
            ])->toArray();

            MensajeVisto::insert($registros);
        }
    }


    /**
     * Helper: Sanitizar contenido HTML
     */
    private function sanitizarContenido(string $contenido): string
    {
        // Permitir solo emojis y texto plano, eliminar tags HTML peligrosos
        $contenido = strip_tags($contenido, '<br><b><i><u><s><a>');
        
        // Escapar atributos peligrosos en links
        $contenido = preg_replace('/(<a[^>]*)\s+(on\w+|javascript)[^>]*/i', '$1', $contenido);
        
        return $contenido;
    }
}
