<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoConversacion extends JsonResource
{
    /**
     * Transformar el recurso en un array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre_mostrar,
            'es_grupo' => $this->es_grupo,
            'creado_por' => $this->creado_por,
            'creado_por_usuario' => $this->whenLoaded('creador', function () {
                return [
                    'id' => $this->creador->id,
                    'name' => $this->creador->name,
                ];
            }),
            'usuarios' => RecursoUsuarioChat::collection($this->whenLoaded('usuarios')),
            'ultimo_mensaje' => $this->when(
                $this->relationLoaded('ultimoMensaje') && $this->ultimoMensaje,
                function () {
                    return [
                        'id' => $this->ultimoMensaje->id,
                        'contenido' => $this->ultimoMensaje->preview,
                        'usuario' => [
                            'id' => $this->ultimoMensaje->usuario->id,
                            'name' => $this->ultimoMensaje->usuario->name,
                        ],
                        'creado_el' => $this->ultimoMensaje->creado_el?->toIso8601String(),
                    ];
                }
            ),
            'mensajes_no_leidos' => $userId ? $this->mensajesNoLeidos($userId) : 0,
            'tiene_mensajes_nuevos' => $userId ? $this->tieneMensajesNuevos($userId) : false,
            'es_admin' => $userId ? $this->esAdmin($userId) : false,
            'archivada' => $userId ? $this->usuarios->find($userId)?->pivot->archivada ?? false : false,
            'creado_el' => $this->creado_el?->toIso8601String(),
            'actualizado_el' => $this->actualizado_el?->toIso8601String(),
        ];
    }
}
