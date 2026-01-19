<?php

namespace App\Http\Controllers\Inmuebles;

use App\Http\Controllers\Controller;
use App\Models\CuentaBancaria;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorCuentasBancarias extends Controller
{
    /**
     * Listar cuentas bancarias de un inmueble
     */
    public function index(Request $request, int $inmuebleId): JsonResponse
    {
        $this->verificarAcceso($request->user(), $inmuebleId);

        $query = CuentaBancaria::where('inmueble_id', $inmuebleId);

        // Filtros opcionales
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activa')) {
            $query->where('activa', filter_var($request->activa, FILTER_VALIDATE_BOOLEAN));
        }

        $cuentas = $query->orderBy('banco')->get();

        return response()->json([
            'datos' => $cuentas,
        ]);
    }

    /**
     * Mostrar una cuenta bancaria específica
     */
    public function mostrar(Request $request, int $inmuebleId, int $id): JsonResponse
    {
        $this->verificarAcceso($request->user(), $inmuebleId);

        $cuenta = CuentaBancaria::where('inmueble_id', $inmuebleId)
            ->findOrFail($id);

        return response()->json([
            'datos' => $cuenta,
        ]);
    }

    /**
     * Crear una nueva cuenta bancaria
     */
    public function almacenar(Request $request, int $inmuebleId): JsonResponse
    {
        $this->verificarAcceso($request->user(), $inmuebleId, 'administrador');

        // Verificar que el inmueble existe
        Inmueble::findOrFail($inmuebleId);

        $validados = $request->validate([
            'banco' => 'required|string|max:255',
            'titular' => 'required|string|max:255',
            'numero_cuenta' => 'required|string|max:50',
            'clabe' => 'nullable|string|size:18',
            'tipo' => 'required|string|in:operacion,mantenimiento,reserva',
            'activa' => 'nullable|boolean',
        ]);

        $validados['inmueble_id'] = $inmuebleId;
        $cuenta = CuentaBancaria::create($validados);

        return response()->json([
            'mensaje' => 'Cuenta bancaria creada exitosamente',
            'datos' => $cuenta,
        ], 201);
    }

    /**
     * Actualizar una cuenta bancaria
     */
    public function actualizar(Request $request, int $inmuebleId, int $id): JsonResponse
    {
        $this->verificarAcceso($request->user(), $inmuebleId, 'administrador');

        $cuenta = CuentaBancaria::where('inmueble_id', $inmuebleId)
            ->findOrFail($id);

        $validados = $request->validate([
            'banco' => 'sometimes|string|max:255',
            'titular' => 'sometimes|string|max:255',
            'numero_cuenta' => 'sometimes|string|max:50',
            'clabe' => 'nullable|string|size:18',
            'tipo' => 'sometimes|string|in:operacion,mantenimiento,reserva',
            'activa' => 'nullable|boolean',
        ]);

        $cuenta->update($validados);

        return response()->json([
            'mensaje' => 'Cuenta bancaria actualizada exitosamente',
            'datos' => $cuenta->fresh(),
        ]);
    }

    /**
     * Eliminar una cuenta bancaria
     */
    public function eliminar(Request $request, int $inmuebleId, int $id): JsonResponse
    {
        $this->verificarAcceso($request->user(), $inmuebleId, 'administrador');

        $cuenta = CuentaBancaria::where('inmueble_id', $inmuebleId)
            ->findOrFail($id);

        $cuenta->delete();

        return response()->json([
            'mensaje' => 'Cuenta bancaria eliminada exitosamente',
        ], 200);
    }

    /**
     * Verificar que el usuario tenga acceso al inmueble
     */
    private function verificarAcceso($usuario, int $inmuebleId, ?string $rolMinimo = null): void
    {
        if ($usuario->hasRole('super-admin')) {
            return;
        }

        $query = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $inmuebleId)
            ->activos();

        if ($rolMinimo === 'administrador') {
            $query->whereIn('rol', ['administrador', 'supervisor']);
        }

        if (!$query->exists()) {
            abort(403, 'No tienes acceso a este inmueble');
        }
    }
}
