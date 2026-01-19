<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudCrearConversacion extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        return true; // Cualquier usuario autenticado puede crear conversaciones
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['nullable', 'string', 'max:255'],
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['required', 'integer', 'exists:users,id'],
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
            'usuarios.required' => 'Debes seleccionar al menos un usuario.',
            'usuarios.array' => 'Los usuarios deben ser un array.',
            'usuarios.min' => 'Debes seleccionar al menos un usuario.',
            'usuarios.*.required' => 'Cada usuario debe ser válido.',
            'usuarios.*.integer' => 'El ID del usuario debe ser un número.',
            'usuarios.*.exists' => 'Uno o más usuarios seleccionados no existen.',
            'nombre.max' => 'El nombre de la conversación no puede exceder 255 caracteres.',
        ];
    }
}
