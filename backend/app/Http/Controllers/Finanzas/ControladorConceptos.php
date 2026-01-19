<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Concepto;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorConceptos extends Controller
{
    /**
     * Listar conceptos de cobro
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Concepto::with('inmueble');

        // Filtrar por inmueble
        if ($request->filled('inmueble_id')) {
            $this->verificarAccesoInmueble($usuario, $request->inmueble_id);
            $query->where('inmueble_id', $request->inmueble_id);
        } else {
            $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);
            $query->whereIn('inmueble_id', $inmuebleIds);
        }

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $conceptos = $query->orderBy('nombre')->get();

        return response()->json([
            'datos' => $conceptos,
        ]);
    }

    /**
     * Mostrar un concepto específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $concepto = Concepto::with('inmueble')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $concepto->inmueble_id);

        return response()->json([
            'datos' => $concepto,
        ]);
    }

    /**
     * Crear un nuevo concepto
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'inmueble_id' => 'required|exists:inmuebles,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|string|in:fijo,variable,extraordinario',
            'monto_default' => 'nullable|numeric|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $this->verificarAccesoInmueble($request->user(), $validados['inmueble_id'], 'administrador');

        $validados['activo'] = $validados['activo'] ?? true;
        $concepto = Concepto::create($validados);

        return response()->json([
            'mensaje' => 'Concepto creado exitosamente',
            'datos' => $concepto->load('inmueble'),
        ], 201);
    }

    /**
     * Actualizar un concepto
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $concepto = Concepto::findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $concepto->inmueble_id, 'administrador');

        $validados = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'sometimes|string|in:fijo,variable,extraordinario',
            'monto_default' => 'nullable|numeric|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $concepto->update($validados);

        return response()->json([
            'mensaje' => 'Concepto actualizado exitosamente',
            'datos' => $concepto->fresh()->load('inmueble'),
        ]);
    }

    /**
     * Eliminar un concepto
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $concepto = Concepto::withCount('cargos')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $concepto->inmueble_id, 'administrador');

        if ($concepto->cargos_count > 0) {
            return response()->json([
                'mensaje' => 'No se puede eliminar el concepto porque tiene cargos asociados',
                'cargos_count' => $concepto->cargos_count,
            ], 422);
        }

        $concepto->delete();

        return response()->json([
            'mensaje' => 'Concepto eliminado exitosamente',
        ], 200);
    }

    /**
     * Obtener IDs de inmuebles accesibles
     */
    private function obtenerInmueblesAccesibles($usuario): \Illuminate\Support\Collection
    {
        if ($usuario->hasRole('super-admin')) {
            return Inmueble::pluck('id');
        }

        return InmuebleUsuario::where('usuario_id', $usuario->id)
            ->activos()
            ->pluck('inmueble_id');
    }

    /**
     * Verificar acceso al inmueble
     */
    private function verificarAccesoInmueble($usuario, int $inmuebleId, ?string $rolMinimo = null): void
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
