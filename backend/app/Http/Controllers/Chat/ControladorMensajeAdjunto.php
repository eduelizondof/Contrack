<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SolicitudSubirAdjunto;
use App\Http\Resources\Chat\RecursoMensaje;
use App\Models\Chat\Conversacion;
use App\Models\Chat\Mensaje;
use App\Models\Chat\MensajeAdjunto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorMensajeAdjunto extends Controller
{
    /**
     * Subir archivo con mensaje
     */
    public function store(SolicitudSubirAdjunto $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();

        // Verificar acceso
        if (!$conversacion->perteneceUsuario($user->id)) {
            return response()->json([
                'mensaje' => 'No tienes acceso a esta conversación',
            ], 403);
        }

        $archivo = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        // Validar extensión (validación adicional por si acaso)
        if (!MensajeAdjunto::extensionPermitida($extension)) {
            return response()->json([
                'mensaje' => 'Tipo de archivo no permitido',
                'extensiones_permitidas' => collect(MensajeAdjunto::EXTENSIONES_PERMITIDAS)->flatten()->toArray(),
            ], 422);
        }

        // Determinar tipo
        $tipoAdjunto = MensajeAdjunto::determinarTipo($extension);
        $tipoMensaje = $tipoAdjunto === 'imagen' ? 'imagen' : 'archivo';

        // Guardar archivo
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
        $ruta = $archivo->storeAs(
            'chat/' . $conversacion->id,
            $nombreArchivo,
            'public'
        );

        if (!$ruta) {
            return response()->json([
                'mensaje' => 'Error al guardar el archivo',
            ], 500);
        }

        // Crear mensaje
        $mensaje = Mensaje::create([
            'conversacion_id' => $conversacion->id,
            'user_id' => $user->id,
            'responde_a_id' => $request->responde_a_id,
            'tipo' => $tipoMensaje,
            'contenido' => $request->contenido,
        ]);

        // Crear adjunto
        $adjunto = MensajeAdjunto::create([
            'mensaje_id' => $mensaje->id,
            'tipo' => $tipoAdjunto,
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime' => $archivo->getMimeType(),
            'peso' => $archivo->getSize(),
        ]);

        $mensaje->load(['usuario:id,name,email', 'adjuntos', 'respondeA.usuario:id,name']);

        // Actualizar último visto del usuario
        $conversacion->usuarios()->updateExistingPivot($user->id, [
            'ultimo_visto_at' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Archivo enviado',
            'datos' => new RecursoMensaje($mensaje),
        ], 201);
    }

    /**
     * Eliminar adjunto (y su mensaje si no tiene contenido)
     */
    public function destroy(Request $request, MensajeAdjunto $adjunto): JsonResponse
    {
        $user = $request->user();
        $mensaje = $adjunto->mensaje;

        // Verificar que es el autor del mensaje
        if ($mensaje->user_id !== $user->id) {
            return response()->json([
                'mensaje' => 'No puedes eliminar este adjunto',
            ], 403);
        }

        // Eliminar archivo del storage
        Storage::disk('public')->delete($adjunto->ruta);

        // Eliminar registro
        $adjunto->delete();

        // Si el mensaje no tiene más adjuntos ni contenido, eliminarlo
        if ($mensaje->adjuntos()->count() === 0 && empty($mensaje->contenido)) {
            $mensaje->update(['eliminado' => true]);
        }

        return response()->json([
            'mensaje' => 'Adjunto eliminado',
        ]);
    }
}
