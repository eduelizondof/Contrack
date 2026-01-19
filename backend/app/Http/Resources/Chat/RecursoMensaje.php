<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoMensaje extends JsonResource
{
    /**
     * Transformar el recurso en un array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        // Calcular vistos_count
        $vistosCount = 0;
        if ($this->relationLoaded('vistos')) {
            $vistosCount = $this->vistos->where('user_id', '!=', $this->user_id)->count();
        } else {
            $vistosCount = $this->vistos()->where('user_id', '!=', $this->user_id)->count();
        }

        // Calcular visto_por_todos
        $vistoPorTodos = false;
        if ($this->relationLoaded('conversacion') && $this->conversacion->relationLoaded('usuarios')) {
            // Obtener todos los participantes excepto el autor del mensaje
            $participantes = $this->conversacion->usuarios->pluck('id')->reject($this->user_id);
            
            if ($participantes->isNotEmpty()) {
                // Obtener IDs de usuarios que vieron el mensaje (excluyendo al autor)
                if ($this->relationLoaded('vistos')) {
                    $vistosIds = $this->vistos->where('user_id', '!=', $this->user_id)->pluck('user_id');
                } else {
                    $vistosIds = $this->vistos()->where('user_id', '!=', $this->user_id)->pluck('user_id');
                }
                
                // Verificar si todos los participantes (excepto el autor) vieron el mensaje
                $vistoPorTodos = $participantes->diff($vistosIds)->isEmpty();
            }
        }

        return [
            'id' => $this->id,
            'conversacion_id' => $this->conversacion_id,
            'tipo' => $this->tipo,
            'contenido' => $this->eliminado ? null : $this->contenido,
            'editado' => $this->editado,
            'eliminado' => $this->eliminado,
            'usuario' => [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ],
            'es_propio' => $this->user_id === $userId,
            'responde_a' => $this->when(
                $this->respondeA,
                function () {
                    return [
                        'id' => $this->respondeA->id,
                        'contenido' => $this->respondeA->preview,
                        'usuario' => [
                            'id' => $this->respondeA->usuario->id,
                            'name' => $this->respondeA->usuario->name,
                        ],
                    ];
                }
            ),
            'adjuntos' => RecursoMensajeAdjunto::collection($this->whenLoaded('adjuntos')),
            'vistos' => $this->whenLoaded('vistos', function () {
                return $this->vistos->map(function ($visto) {
                    return [
                        'id' => $visto->usuario->id,
                        'name' => $visto->usuario->name,
                    ];
                });
            }),
            'vistos_count' => $vistosCount,
            'visto_por_todos' => $vistoPorTodos,
            'creado_el' => $this->creado_el?->toIso8601String(),
            'actualizado_el' => $this->actualizado_el?->toIso8601String(),
        ];
    }
}
