<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Concepto;
use App\Models\Contrato;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorCargos extends Controller
{
    /**
     * Listar cargos
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Cargo::with(['unidad.inmueble', 'concepto', 'contrato']);

        // Filtrar por inmuebles accesibles
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);
        $query->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds));

        // Filtros
        if ($request->filled('unidad_id')) {
            $query->where('unidad_id', $request->unidad_id);
        }

        if ($request->filled('concepto_id')) {
            $query->where('concepto_id', $request->concepto_id);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo', $request->periodo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('con_adeudo')) {
            $query->conAdeudo();
        }

        // Paginación
        $porPagina = $request->input('por_pagina', 15);
        $cargos = $query->orderByDesc('fecha_vencimiento')->paginate($porPagina);

        return response()->json([
            'datos' => $cargos->items(),
            'meta' => [
                'total' => $cargos->total(),
                'por_pagina' => $cargos->perPage(),
                'pagina_actual' => $cargos->currentPage(),
                'ultima_pagina' => $cargos->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar un cargo específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $cargo = Cargo::with(['unidad.inmueble', 'concepto', 'contrato', 'pagos.usuario'])->findOrFail($id);
        $this->verificarAccesoUnidad($request->user(), $cargo->unidad_id);

        // Agregar información calculada
        $cargo->monto_pagado = $cargo->monto_pagado;
        $cargo->saldo_pendiente = $cargo->saldo_pendiente;

        return response()->json([
            'datos' => $cargo,
        ]);
    }

    /**
     * Crear un nuevo cargo
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'contrato_id' => 'nullable|exists:contratos,id',
            'periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'monto' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'estado' => 'nullable|string|in:pendiente,pagado,vencido,cancelado',
            'notas' => 'nullable|string',
        ]);

        $unidad = Unidad::findOrFail($validados['unidad_id']);
        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id, 'administrador');

        // Verificar que el concepto pertenezca al mismo inmueble
        $concepto = Concepto::findOrFail($validados['concepto_id']);
        if ($concepto->inmueble_id !== $unidad->inmueble_id) {
            return response()->json([
                'mensaje' => 'El concepto no pertenece al mismo inmueble que la unidad',
            ], 422);
        }

        $validados['estado'] = $validados['estado'] ?? 'pendiente';
        $cargo = Cargo::create($validados);

        return response()->json([
            'mensaje' => 'Cargo creado exitosamente',
            'datos' => $cargo->load(['unidad.inmueble', 'concepto']),
        ], 201);
    }

    /**
     * Actualizar un cargo
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $cargo = Cargo::with('unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $cargo->unidad->inmueble_id, 'administrador');

        $validados = $request->validate([
            'monto' => 'sometimes|numeric|min:0',
            'fecha_vencimiento' => 'sometimes|date',
            'estado' => 'sometimes|string|in:pendiente,pagado,vencido,cancelado',
            'notas' => 'nullable|string',
        ]);

        $cargo->update($validados);

        return response()->json([
            'mensaje' => 'Cargo actualizado exitosamente',
            'datos' => $cargo->fresh()->load(['unidad.inmueble', 'concepto']),
        ]);
    }

    /**
     * Eliminar un cargo
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $cargo = Cargo::with('unidad')->withCount('pagos')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $cargo->unidad->inmueble_id, 'administrador');

        if ($cargo->pagos_count > 0) {
            return response()->json([
                'mensaje' => 'No se puede eliminar el cargo porque tiene pagos registrados',
                'pagos_count' => $cargo->pagos_count,
            ], 422);
        }

        $cargo->delete();

        return response()->json([
            'mensaje' => 'Cargo eliminado exitosamente',
        ], 200);
    }

    /**
     * Obtener cargos pendientes
     */
    public function pendientes(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);

        $cargos = Cargo::with(['unidad.inmueble', 'concepto'])
            ->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds))
            ->pendientes()
            ->orderBy('fecha_vencimiento')
            ->get();

        $total = $cargos->sum('monto');

        return response()->json([
            'datos' => $cargos,
            'resumen' => [
                'total_cargos' => $cargos->count(),
                'monto_total' => $total,
            ],
        ]);
    }

    /**
     * Obtener cargos vencidos
     */
    public function vencidos(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);

        $cargos = Cargo::with(['unidad.inmueble', 'concepto'])
            ->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds))
            ->vencidos()
            ->orderBy('fecha_vencimiento')
            ->get();

        $total = $cargos->sum('monto');

        return response()->json([
            'datos' => $cargos,
            'resumen' => [
                'total_cargos' => $cargos->count(),
                'monto_total' => $total,
            ],
        ]);
    }

    /**
     * Generar cargos recurrentes para un periodo
     */
    public function generarCargosRecurrentes(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'inmueble_id' => 'required|exists:inmuebles,id',
            'periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'dia_vencimiento' => 'required|integer|min:1|max:31',
        ]);

        $this->verificarAccesoInmueble($request->user(), $validados['inmueble_id'], 'administrador');

        // Obtener conceptos fijos activos del inmueble
        $conceptosFijos = Concepto::where('inmueble_id', $validados['inmueble_id'])
            ->fijos()
            ->activos()
            ->get();

        // Obtener unidades con contrato activo
        $unidades = Unidad::where('inmueble_id', $validados['inmueble_id'])
            ->conContratoActivo()
            ->with(['contratos' => fn ($q) => $q->activos()])
            ->get();

        $cargosCreados = 0;
        $cargosExistentes = 0;

        foreach ($unidades as $unidad) {
            $contratoActivo = $unidad->contratos->first();

            foreach ($conceptosFijos as $concepto) {
                // Verificar si ya existe un cargo para este periodo
                $existe = Cargo::where('unidad_id', $unidad->id)
                    ->where('concepto_id', $concepto->id)
                    ->where('periodo', $validados['periodo'])
                    ->exists();

                if ($existe) {
                    $cargosExistentes++;
                    continue;
                }

                // Calcular fecha de vencimiento
                $fechaVencimiento = \Carbon\Carbon::createFromFormat(
                    'Y-m-d',
                    $validados['periodo'] . '-' . str_pad($validados['dia_vencimiento'], 2, '0', STR_PAD_LEFT)
                );

                Cargo::create([
                    'unidad_id' => $unidad->id,
                    'concepto_id' => $concepto->id,
                    'contrato_id' => $contratoActivo?->id,
                    'periodo' => $validados['periodo'],
                    'monto' => $concepto->monto_default ?? 0,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado' => 'pendiente',
                ]);

                $cargosCreados++;
            }
        }

        return response()->json([
            'mensaje' => "Cargos generados: {$cargosCreados}. Ya existentes: {$cargosExistentes}.",
            'datos' => [
                'cargos_creados' => $cargosCreados,
                'cargos_existentes' => $cargosExistentes,
            ],
        ]);
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
     * Verificar acceso a la unidad
     */
    private function verificarAccesoUnidad($usuario, int $unidadId): void
    {
        $unidad = Unidad::findOrFail($unidadId);
        $this->verificarAccesoInmueble($usuario, $unidad->inmueble_id);
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
