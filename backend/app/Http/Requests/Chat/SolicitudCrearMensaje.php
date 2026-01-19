<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudCrearMensaje extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        return true; // El controlador verifica el acceso a la conversación
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
            'tipo' => ['nullable', 'string', 'in:texto,link'],
            'responde_a_id' => ['nullable', 'integer', 'exists:mensajes,id'],
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
            'tipo.in' => 'El tipo de mensaje debe ser "texto" o "link".',
            'responde_a_id.integer' => 'El ID del mensaje de respuesta debe ser un número.',
            'responde_a_id.exists' => 'El mensaje al que intentas responder no existe.',
        ];
    }
}
