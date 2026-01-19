<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoMensajeAdjunto extends JsonResource
{
    /**
     * Transformar el recurso en un array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'nombre_original' => $this->nombre_original,
            'url' => $this->url,
            'peso' => $this->peso,
            'peso_formateado' => $this->peso_formateado,
            'es_imagen' => $this->es_imagen,
            'mime' => $this->mime,
            'creado_el' => $this->creado_el?->toIso8601String(),
        ];
    }
}
