<?php

namespace App\Http\Resources\Notificaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoNotificacion extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoria' => [
                'tipo' => $this->categoria->tipo ?? null,
                'nombre' => $this->categoria->nombre ?? null,
                'icono' => $this->categoria->icono ?? null,
                'color' => $this->categoria->color ?? null,
            ],
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'prioridad' => $this->prioridad,
            'leido' => $this->leido ?? false,
            'leido_el' => $this->leido_el?->toISOString(),
            'accion' => [
                'tipo' => $this->accion_tipo,
                'ruta' => $this->accion_ruta,
                'parametros' => $this->accion_parametros ?? [],
            ],
            'datos' => $this->datos_json ?? [],
            'creado_el' => $this->creado_el->toISOString(),
        ];
    }
}
