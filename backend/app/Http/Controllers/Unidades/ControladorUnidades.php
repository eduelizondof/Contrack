<?php

namespace App\Http\Controllers\Unidades;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorUnidades extends Controller
{
    /**
     * Listar unidades (filtradas por inmueble o acceso del usuario)
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Unidad::with('inmueble');

        // Filtrar por inmueble específico
        if ($request->filled('inmueble_id')) {
            $this->verificarAccesoInmueble($usuario, $request->inmueble_id);
            $query->where('inmueble_id', $request->inmueble_id);
        } else {
            // Filtrar por inmuebles accesibles
            $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);
            $query->whereIn('inmueble_id', $inmuebleIds);
        }

        // Filtros opcionales
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('buscar')) {
            $query->where('identificador', 'like', "%{$request->buscar}%");
        }

        if ($request->has('con_adeudos')) {
            $request->boolean('con_adeudos') ? $query->conAdeudos() : $query->sinAdeudos();
        }

        if ($request->has('con_contrato_activo')) {
            $query->conContratoActivo();
        }

        // Paginación
        $porPagina = $request->input('por_pagina', 15);
        $unidades = $query->orderBy('identificador')->paginate($porPagina);

        // Agregar saldo pendiente a cada unidad
        $unidades->getCollection()->transform(function ($unidad) {
            $unidad->saldo_pendiente = $unidad->saldo_pendiente;
            return $unidad;
        });

        return response()->json([
            'datos' => $unidades->items(),
            'meta' => [
                'total' => $unidades->total(),
                'por_pagina' => $unidades->perPage(),
                'pagina_actual' => $unidades->currentPage(),
                'ultima_pagina' => $unidades->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar una unidad específica
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $unidad = Unidad::with([
            'inmueble',
            'contratos' => fn ($q) => $q->orderByDesc('fecha_inicio'),
            'inmuebleUsuarios.usuario',
        ])->findOrFail($id);

        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id);

        // Agregar información adicional
        $unidad->saldo_pendiente = $unidad->saldo_pendiente;
        $unidad->contrato_activo = $unidad->contrato_activo;

        return response()->json([
            'datos' => $unidad,
        ]);
    }

    /**
     * Crear una nueva unidad
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'inmueble_id' => 'required|exists:inmuebles,id',
            'identificador' => 'required|string|max:50',
            'tipo' => 'required|string|in:casa,departamento,local,bodega',
            'nivel' => 'nullable|string|max:50',
            'area_m2' => 'nullable|numeric|min:0',
            'recamaras' => 'nullable|integer|min:0',
            'banos' => 'nullable|numeric|min:0',
            'estacionamientos' => 'nullable|integer|min:0',
            'caracteristicas' => 'nullable|array',
        ]);

        $this->verificarAccesoInmueble($request->user(), $validados['inmueble_id'], 'administrador');

        // Verificar unicidad del identificador en el inmueble
        $existe = Unidad::where('inmueble_id', $validados['inmueble_id'])
            ->where('identificador', $validados['identificador'])
            ->exists();

        if ($existe) {
            return response()->json([
                'mensaje' => 'Ya existe una unidad con ese identificador en este inmueble',
            ], 422);
        }

        $unidad = Unidad::create($validados);

        // Actualizar contador de unidades en inmueble
        Inmueble::where('id', $validados['inmueble_id'])
            ->increment('total_unidades');

        return response()->json([
            'mensaje' => 'Unidad creada exitosamente',
            'datos' => $unidad->load('inmueble'),
        ], 201);
    }

    /**
     * Actualizar una unidad existente
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $unidad = Unidad::findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id, 'administrador');

        $validados = $request->validate([
            'identificador' => 'sometimes|string|max:50',
            'tipo' => 'sometimes|string|in:casa,departamento,local,bodega',
            'nivel' => 'nullable|string|max:50',
            'area_m2' => 'nullable|numeric|min:0',
            'recamaras' => 'nullable|integer|min:0',
            'banos' => 'nullable|numeric|min:0',
            'estacionamientos' => 'nullable|integer|min:0',
            'caracteristicas' => 'nullable|array',
        ]);

        // Verificar unicidad si cambia el identificador
        if (isset($validados['identificador']) && $validados['identificador'] !== $unidad->identificador) {
            $existe = Unidad::where('inmueble_id', $unidad->inmueble_id)
                ->where('identificador', $validados['identificador'])
                ->where('id', '!=', $id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'mensaje' => 'Ya existe una unidad con ese identificador en este inmueble',
                ], 422);
            }
        }

        $unidad->update($validados);

        return response()->json([
            'mensaje' => 'Unidad actualizada exitosamente',
            'datos' => $unidad->fresh()->load('inmueble'),
        ]);
    }

    /**
     * Eliminar una unidad (soft delete)
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $unidad = Unidad::withCount(['contratos', 'cargos', 'tickets'])->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id, 'administrador');

        // Verificar que no tenga contratos activos
        $contratosActivos = $unidad->contratos()->activos()->count();
        if ($contratosActivos > 0) {
            return response()->json([
                'mensaje' => 'No se puede eliminar la unidad porque tiene contratos activos',
                'contratos_activos' => $contratosActivos,
            ], 422);
        }

        // Verificar que no tenga adeudos pendientes
        $adeudosPendientes = $unidad->cargos()->conAdeudo()->count();
        if ($adeudosPendientes > 0) {
            return response()->json([
                'mensaje' => 'No se puede eliminar la unidad porque tiene adeudos pendientes',
                'adeudos_pendientes' => $adeudosPendientes,
            ], 422);
        }

        $inmuebleId = $unidad->inmueble_id;
        $unidad->delete();

        // Actualizar contador de unidades en inmueble
        Inmueble::where('id', $inmuebleId)->decrement('total_unidades');

        return response()->json([
            'mensaje' => 'Unidad eliminada exitosamente',
        ], 200);
    }

    /**
     * Obtener adeudos de una unidad
     */
    public function adeudos(Request $request, int $id): JsonResponse
    {
        $unidad = Unidad::findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id);

        $cargos = Cargo::with(['concepto', 'pagos'])
            ->where('unidad_id', $id)
            ->conAdeudo()
            ->orderBy('fecha_vencimiento')
            ->get();

        $resumen = [
            'total_adeudo' => $cargos->sum('monto'),
            'cargos_vencidos' => $cargos->where('estado', 'vencido')->count(),
            'cargos_pendientes' => $cargos->where('estado', 'pendiente')->count(),
        ];

        return response()->json([
            'datos' => [
                'unidad' => [
                    'id' => $unidad->id,
                    'identificador' => $unidad->identificador,
                ],
                'resumen' => $resumen,
                'cargos' => $cargos,
            ],
        ]);
    }

    /**
     * Obtener IDs de inmuebles accesibles para el usuario
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
