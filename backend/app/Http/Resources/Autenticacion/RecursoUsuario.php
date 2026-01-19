<?php

namespace App\Http\Resources\Autenticacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoUsuario extends JsonResource
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
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'creado_el' => $this->created_at->format('Y-m-d H:i:s'),
            'actualizado_el' => $this->updated_at->format('Y-m-d H:i:s'),
            'roles' => $this->getRoleNames(),
            'permisos' => $this->getAllPermissions()->pluck('name')->toArray(),
        ];
    }
}
