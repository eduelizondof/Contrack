<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudCrearUsuario extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        // Verificar que el usuario tenga permiso de crear usuarios
        return $this->user()->can('configuracion.crear.usuario');
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol_id' => ['sometimes', 'nullable', 'exists:roles,id'],
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
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
        ];
    }
}
