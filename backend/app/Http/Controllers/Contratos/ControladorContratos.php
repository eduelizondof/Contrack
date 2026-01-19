<?php

namespace App\Http\Controllers\Contratos;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorContratos extends Controller
{
    /**
     * Listar contratos
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Contrato::with(['unidad.inmueble', 'usuario']);

        // Filtrar por inmuebles accesibles
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);
        $query->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds));

        // Filtros
        if ($request->filled('unidad_id')) {
            $query->where('unidad_id', $request->unidad_id);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('activos')) {
            $query->activos();
        }

        // Paginación
        $porPagina = $request->input('por_pagina', 15);
        $contratos = $query->orderByDesc('fecha_inicio')->paginate($porPagina);

        return response()->json([
            'datos' => $contratos->items(),
            'meta' => [
                'total' => $contratos->total(),
                'por_pagina' => $contratos->perPage(),
                'pagina_actual' => $contratos->currentPage(),
                'ultima_pagina' => $contratos->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar un contrato específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $contrato = Contrato::with(['unidad.inmueble', 'usuario', 'cargos'])->findOrFail($id);
        $this->verificarAccesoContrato($request->user(), $contrato);

        // Agregar información adicional
        $contrato->esta_vigente = $contrato->esta_vigente;
        $contrato->dias_restantes = $contrato->dias_restantes;

        return response()->json([
            'datos' => $contrato,
        ]);
    }

    /**
     * Crear un nuevo contrato
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'usuario_id' => 'required|exists:users,id',
            'tipo' => 'required|string|in:renta,propiedad',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'monto_mensual' => 'nullable|numeric|min:0',
            'dia_pago' => 'nullable|integer|min:1|max:31',
            'renovacion_automatica' => 'nullable|boolean',
            'archivo_contrato' => 'nullable|string',
            'estado' => 'nullable|string|in:activo,finalizado,cancelado',
        ]);

        $unidad = Unidad::findOrFail($validados['unidad_id']);
        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id, 'administrador');

        // Verificar que no exista otro contrato activo en la misma unidad
        $contratoActivo = Contrato::where('unidad_id', $validados['unidad_id'])
            ->activos()
            ->first();

        if ($contratoActivo) {
            return response()->json([
                'mensaje' => 'Ya existe un contrato activo para esta unidad',
                'contrato_existente' => $contratoActivo->id,
            ], 422);
        }

        // Si es renta, el monto mensual es requerido
        if ($validados['tipo'] === 'renta' && empty($validados['monto_mensual'])) {
            return response()->json([
                'mensaje' => 'El monto mensual es requerido para contratos de renta',
            ], 422);
        }

        $validados['estado'] = $validados['estado'] ?? 'activo';
        $contrato = Contrato::create($validados);

        // Crear asignación de usuario a la unidad como inquilino o propietario
        $rolUsuario = $validados['tipo'] === 'renta' ? 'inquilino' : 'propietario';
        InmuebleUsuario::firstOrCreate(
            [
                'inmueble_id' => $unidad->inmueble_id,
                'usuario_id' => $validados['usuario_id'],
                'rol' => $rolUsuario,
                'unidad_id' => $unidad->id,
            ],
            [
                'fecha_inicio' => $validados['fecha_inicio'],
                'fecha_fin' => $validados['fecha_fin'],
                'activo' => true,
            ]
        );

        return response()->json([
            'mensaje' => 'Contrato creado exitosamente',
            'datos' => $contrato->load(['unidad.inmueble', 'usuario']),
        ], 201);
    }

    /**
     * Actualizar un contrato existente
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $contrato = Contrato::with('unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $contrato->unidad->inmueble_id, 'administrador');

        $validados = $request->validate([
            'tipo' => 'sometimes|string|in:renta,propiedad',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'monto_mensual' => 'nullable|numeric|min:0',
            'dia_pago' => 'nullable|integer|min:1|max:31',
            'renovacion_automatica' => 'nullable|boolean',
            'archivo_contrato' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,finalizado,cancelado',
        ]);

        $contrato->update($validados);

        return response()->json([
            'mensaje' => 'Contrato actualizado exitosamente',
            'datos' => $contrato->fresh()->load(['unidad.inmueble', 'usuario']),
        ]);
    }

    /**
     * Eliminar (soft delete) un contrato
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $contrato = Contrato::with('unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $contrato->unidad->inmueble_id, 'administrador');

        // Verificar que no tenga cargos pendientes
        $cargosPendientes = $contrato->cargos()->conAdeudo()->count();
        if ($cargosPendientes > 0) {
            return response()->json([
                'mensaje' => 'No se puede eliminar el contrato porque tiene cargos pendientes',
                'cargos_pendientes' => $cargosPendientes,
            ], 422);
        }

        $contrato->delete();

        return response()->json([
            'mensaje' => 'Contrato eliminado exitosamente',
        ], 200);
    }

    /**
     * Obtener contratos activos
     */
    public function activos(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);

        $contratos = Contrato::with(['unidad.inmueble', 'usuario'])
            ->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds))
            ->activos()
            ->orderBy('fecha_inicio')
            ->get();

        return response()->json([
            'datos' => $contratos,
        ]);
    }

    /**
     * Obtener contratos próximos a vencer
     */
    public function porVencer(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $dias = $request->input('dias', 30);
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);

        $contratos = Contrato::with(['unidad.inmueble', 'usuario'])
            ->whereHas('unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds))
            ->porVencer($dias)
            ->orderBy('fecha_fin')
            ->get();

        return response()->json([
            'datos' => $contratos,
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
     * Verificar acceso al contrato
     */
    private function verificarAccesoContrato($usuario, $contrato): void
    {
        // El usuario del contrato siempre puede ver su contrato
        if ($contrato->usuario_id === $usuario->id) {
            return;
        }

        $this->verificarAccesoInmueble($usuario, $contrato->unidad->inmueble_id);
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
