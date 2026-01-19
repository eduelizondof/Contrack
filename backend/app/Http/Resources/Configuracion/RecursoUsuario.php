<?php

namespace App\Http\Resources\Configuracion;

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
        // Obtener el primer rol si existe
        $rol = $this->roles->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $rol?->name,
            'rol_id' => $rol?->id,
            'activo' => $this->activo ?? true,
            'creado_el' => $this->created_at?->format('Y-m-d'),
            'ultimo_acceso' => $this->last_login_at?->format('Y-m-d') ?? null, // TODO: Implementar campo
            'horario' => $this->when($request->routeIs('configuracion.usuarios.mostrar'), function () {
                // TODO: Retornar horarios cuando exista la relación
                return $this->generarHorarioDefault();
            }),
        ];
    }

    /**
     * Generar horario por defecto mientras no exista la tabla de horarios
     */
    private function generarHorarioDefault(): array
    {
        $diasSemana = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            0 => 'Domingo',
        ];

        $horarios = [];
        foreach ($diasSemana as $numero => $nombre) {
            $esLaboral = $numero >= 1 && $numero <= 5;
            $horarios[] = [
                'id' => $numero,
                'dia_semana' => $numero,
                'dia_nombre' => $nombre,
                'hora_inicio' => $esLaboral ? '09:00' : null,
                'hora_fin' => $esLaboral ? '18:00' : null,
                'activo' => $esLaboral,
            ];
        }

        return $horarios;
    }
}
