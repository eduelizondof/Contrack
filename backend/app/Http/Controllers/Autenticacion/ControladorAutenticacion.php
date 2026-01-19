<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\SolicitudLogin;
use App\Http\Resources\Autenticacion\RecursoUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ControladorAutenticacion extends Controller
{
    /**
     * Manejar solicitud de inicio de sesión
     */
    public function login(SolicitudLogin $request): JsonResponse
    {
        $credenciales = $request->only('email', 'password');
        $recordar = $request->boolean('remember', false);

        if (!Auth::attempt($credenciales, $recordar)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'usuario' => new RecursoUsuario(Auth::user()),
            'mensaje' => 'Inicio de sesión exitoso',
        ]);
    }

    /**
     * Manejar solicitud de cierre de sesión
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'mensaje' => 'Sesión cerrada exitosamente',
        ]);
    }
}
