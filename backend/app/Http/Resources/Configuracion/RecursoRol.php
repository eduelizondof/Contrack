<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecursoRol extends JsonResource
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
            'nombre' => $this->name,
            'guard_name' => $this->guard_name,
            'usuarios_count' => $this->usuarios_count ?? 0,
            'permisos' => $this->whenLoaded('permissions', function () {
                return $this->permissions->pluck('name')->toArray();
            }, []),
            'creado_el' => $this->created_at?->format('Y-m-d H:i:s'),
            'actualizado_el' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
