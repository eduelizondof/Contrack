<?php

namespace App\Http\Controllers\Expedientes;

use App\Http\Controllers\Controller;
use App\Models\DatoInquilino;
use App\Models\InmuebleUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorDatosInquilinos extends Controller
{
    /**
     * Mostrar datos de inquilino por usuario
     */
    public function mostrar(Request $request, int $usuarioId): JsonResponse
    {
        $this->verificarAccesoUsuario($request->user(), $usuarioId);

        $datos = DatoInquilino::where('usuario_id', $usuarioId)->first();

        if (!$datos) {
            return response()->json([
                'mensaje' => 'No se encontraron datos de inquilino para este usuario',
                'datos' => null,
            ]);
        }

        return response()->json([
            'datos' => $datos,
        ]);
    }

    /**
     * Crear o actualizar datos de inquilino
     */
    public function almacenarOActualizar(Request $request, int $usuarioId): JsonResponse
    {
        $this->verificarAccesoUsuario($request->user(), $usuarioId, true);

        $validados = $request->validate([
            'ocupacion' => 'nullable|string|max:255',
            'empresa' => 'nullable|string|max:255',
            'referencia_nombre' => 'nullable|string|max:255',
            'referencia_telefono' => 'nullable|string|max:20',
            'contacto_emergencia_nombre' => 'nullable|string|max:255',
            'contacto_emergencia_telefono' => 'nullable|string|max:20',
            'numero_personas_habitan' => 'nullable|integer|min:1',
            'tiene_mascotas' => 'nullable|boolean',
            'tipo_mascotas' => 'nullable|string|max:255',
        ]);

        $datos = DatoInquilino::updateOrCreate(
            ['usuario_id' => $usuarioId],
            $validados
        );

        return response()->json([
            'mensaje' => 'Datos de inquilino guardados exitosamente',
            'datos' => $datos,
        ]);
    }

    /**
     * Verificar acceso al usuario
     */
    private function verificarAccesoUsuario($usuario, int $usuarioId, bool $requiereAdmin = false): void
    {
        // El usuario puede editar sus propios datos
        if ($usuario->id === $usuarioId) {
            return;
        }

        if ($usuario->hasRole('super-admin')) {
            return;
        }

        // Verificar que sea administrador de algún inmueble del usuario
        $inmuebleIdsAdmin = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->whereIn('rol', ['administrador', 'supervisor'])
            ->activos()
            ->pluck('inmueble_id');

        $tieneAcceso = InmuebleUsuario::where('usuario_id', $usuarioId)
            ->whereIn('inmueble_id', $inmuebleIdsAdmin)
            ->activos()
            ->exists();

        if (!$tieneAcceso) {
            abort(403, 'No tienes acceso a este usuario');
        }
    }
}
