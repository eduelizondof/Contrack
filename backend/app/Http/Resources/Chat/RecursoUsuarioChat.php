<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoUsuarioChat extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'es_admin' => $this->whenPivotLoaded('conversacion_usuarios', function () {
                return (bool) $this->pivot->es_admin;
            }, false),
        ];
    }
}
