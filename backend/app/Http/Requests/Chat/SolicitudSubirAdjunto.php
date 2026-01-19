<?php

namespace App\Http\Requests\Chat;

use App\Models\Chat\MensajeAdjunto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudSubirAdjunto extends FormRequest
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
        $extensionesPermitidas = collect(MensajeAdjunto::EXTENSIONES_PERMITIDAS)
            ->flatten()
            ->unique()
            ->implode(',');

        return [
            'archivo' => [
                'required',
                'file',
                'max:' . (MensajeAdjunto::TAMANO_MAXIMO / 1024), // KB
                'mimes:' . $extensionesPermitidas,
            ],
            'contenido' => ['nullable', 'string', 'max:1000'],
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
        $extensionesPermitidas = collect(MensajeAdjunto::EXTENSIONES_PERMITIDAS)
            ->flatten()
            ->unique()
            ->implode(', ');

        return [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file' => 'El archivo debe ser válido.',
            'archivo.max' => 'El archivo no puede superar los 10MB.',
            'archivo.mimes' => "El tipo de archivo no está permitido. Extensiones permitidas: {$extensionesPermitidas}.",
            'contenido.max' => 'El mensaje adicional no puede exceder 1000 caracteres.',
            'responde_a_id.integer' => 'El ID del mensaje de respuesta debe ser un número.',
            'responde_a_id.exists' => 'El mensaje al que intentas responder no existe.',
        ];
    }
}
