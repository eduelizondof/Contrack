<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudActualizarRol extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        // Verificar que el usuario tenga permiso de editar roles
        return $this->user()->can('configuracion.editar.rol');
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rolId = $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($rolId),
            ],
            'permisos' => ['sometimes', 'array'],
            'permisos.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Obtener mensajes de error personalizados para las reglas de validación
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
            'permisos.array' => 'Los permisos deben ser un array.',
            'permisos.*.exists' => 'Uno o más permisos no existen.',
        ];
    }
}
