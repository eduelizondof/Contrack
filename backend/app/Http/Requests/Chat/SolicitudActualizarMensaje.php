<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudActualizarMensaje extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        return true; // El controlador verifica que el usuario sea el autor
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contenido' => ['required', 'string', 'max:10000'],
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
            'contenido.required' => 'El mensaje no puede estar vacío.',
            'contenido.string' => 'El contenido del mensaje debe ser texto.',
            'contenido.max' => 'El mensaje es demasiado largo (máximo 10000 caracteres).',
        ];
    }
}
