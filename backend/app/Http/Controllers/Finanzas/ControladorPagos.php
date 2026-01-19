<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorPagos extends Controller
{
    /**
     * Listar pagos
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Pago::with(['cargo.unidad.inmueble', 'cargo.concepto', 'usuario', 'procesador']);

        // Filtrar por inmuebles accesibles
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);
        $query->whereHas('cargo.unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds));

        // Filtros
        if ($request->filled('cargo_id')) {
            $query->where('cargo_id', $request->cargo_id);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('fecha_pago', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Paginación
        $porPagina = $request->input('por_pagina', 15);
        $pagos = $query->orderByDesc('fecha_pago')->paginate($porPagina);

        return response()->json([
            'datos' => $pagos->items(),
            'meta' => [
                'total' => $pagos->total(),
                'por_pagina' => $pagos->perPage(),
                'pagina_actual' => $pagos->currentPage(),
                'ultima_pagina' => $pagos->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar un pago específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $pago = Pago::with(['cargo.unidad.inmueble', 'cargo.concepto', 'usuario', 'procesador'])->findOrFail($id);
        $this->verificarAccesoPago($request->user(), $pago);

        return response()->json([
            'datos' => $pago,
        ]);
    }

    /**
     * Registrar un nuevo pago
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'cargo_id' => 'required|exists:cargos,id',
            'metodo_pago' => 'required|string|in:transferencia,efectivo,tarjeta,pasarela',
            'referencia' => 'nullable|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'nullable|date',
            'comprobante_url' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $cargo = Cargo::with('unidad')->findOrFail($validados['cargo_id']);

        // Si es inquilino, solo puede pagar sus propios cargos
        $usuario = $request->user();
        $esInquilino = $this->esInquilinoDeUnidad($usuario, $cargo->unidad_id);

        if (!$esInquilino) {
            $this->verificarAccesoInmueble($usuario, $cargo->unidad->inmueble_id, 'administrador');
        }

        // Verificar que el cargo esté pendiente o vencido
        if (!in_array($cargo->estado, ['pendiente', 'vencido'])) {
            return response()->json([
                'mensaje' => 'El cargo no está pendiente de pago',
                'estado_actual' => $cargo->estado,
            ], 422);
        }

        // Determinar estatus inicial
        $estatus = 'pendiente_verificacion';
        if ($esInquilino) {
            $estatus = 'pendiente_verificacion'; // Siempre requiere verificación
        } elseif ($validados['metodo_pago'] === 'efectivo') {
            $estatus = 'pagado'; // Efectivo registrado por admin se confirma automáticamente
            $validados['procesado_por'] = $usuario->id;
        }

        $validados['usuario_id'] = $usuario->id;
        $validados['estatus'] = $estatus;
        $validados['fecha_pago'] = $validados['fecha_pago'] ?? now();

        $pago = Pago::create($validados);

        // Si el pago queda como pagado, actualizar el cargo
        if ($estatus === 'pagado') {
            $this->actualizarEstadoCargo($cargo);
        }

        return response()->json([
            'mensaje' => 'Pago registrado exitosamente',
            'datos' => $pago->load(['cargo.concepto', 'usuario']),
        ], 201);
    }

    /**
     * Verificar un pago (aprobar)
     */
    public function verificar(Request $request, int $id): JsonResponse
    {
        $pago = Pago::with('cargo.unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $pago->cargo->unidad->inmueble_id, 'administrador');

        if ($pago->estatus !== 'pendiente_verificacion') {
            return response()->json([
                'mensaje' => 'El pago no está pendiente de verificación',
                'estatus_actual' => $pago->estatus,
            ], 422);
        }

        $pago->update([
            'estatus' => 'pagado',
            'procesado_por' => $request->user()->id,
        ]);

        // Actualizar estado del cargo
        $this->actualizarEstadoCargo($pago->cargo);

        return response()->json([
            'mensaje' => 'Pago verificado exitosamente',
            'datos' => $pago->fresh()->load(['cargo.concepto', 'usuario', 'procesador']),
        ]);
    }

    /**
     * Rechazar un pago
     */
    public function rechazar(Request $request, int $id): JsonResponse
    {
        $pago = Pago::with('cargo.unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $pago->cargo->unidad->inmueble_id, 'administrador');

        if ($pago->estatus !== 'pendiente_verificacion') {
            return response()->json([
                'mensaje' => 'El pago no está pendiente de verificación',
                'estatus_actual' => $pago->estatus,
            ], 422);
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $pago->update([
            'estatus' => 'rechazado',
            'procesado_por' => $request->user()->id,
            'notas' => ($pago->notas ? $pago->notas . "\n" : '') . "Motivo rechazo: " . $request->motivo,
        ]);

        return response()->json([
            'mensaje' => 'Pago rechazado',
            'datos' => $pago->fresh()->load(['cargo.concepto', 'usuario', 'procesador']),
        ]);
    }

    /**
     * Revertir un pago
     */
    public function revertir(Request $request, int $id): JsonResponse
    {
        $pago = Pago::with('cargo.unidad')->findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $pago->cargo->unidad->inmueble_id, 'administrador');

        if ($pago->estatus !== 'pagado') {
            return response()->json([
                'mensaje' => 'Solo se pueden revertir pagos confirmados',
                'estatus_actual' => $pago->estatus,
            ], 422);
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $pago->update([
            'estatus' => 'revertido',
            'procesado_por' => $request->user()->id,
            'notas' => ($pago->notas ? $pago->notas . "\n" : '') . "Motivo reversión: " . $request->motivo,
        ]);

        // Actualizar estado del cargo (volver a pendiente o vencido)
        $cargo = $pago->cargo;
        if ($cargo->fecha_vencimiento < now()) {
            $cargo->update(['estado' => 'vencido']);
        } else {
            $cargo->update(['estado' => 'pendiente']);
        }

        return response()->json([
            'mensaje' => 'Pago revertido',
            'datos' => $pago->fresh()->load(['cargo.concepto', 'usuario', 'procesador']),
        ]);
    }

    /**
     * Pagos pendientes de verificación
     */
    public function pendientesVerificacion(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);

        $pagos = Pago::with(['cargo.unidad.inmueble', 'cargo.concepto', 'usuario'])
            ->whereHas('cargo.unidad', fn ($q) => $q->whereIn('inmueble_id', $inmuebleIds))
            ->pendientesVerificacion()
            ->orderBy('fecha_pago')
            ->get();

        return response()->json([
            'datos' => $pagos,
            'total' => $pagos->count(),
        ]);
    }

    /**
     * Actualizar estado del cargo basado en pagos
     */
    private function actualizarEstadoCargo($cargo): void
    {
        $totalPagado = $cargo->pagos()->pagados()->sum('monto');

        if ($totalPagado >= $cargo->monto) {
            $cargo->update(['estado' => 'pagado']);
        }
    }

    /**
     * Verificar si el usuario es inquilino de la unidad
     */
    private function esInquilinoDeUnidad($usuario, int $unidadId): bool
    {
        return InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('unidad_id', $unidadId)
            ->where('rol', 'inquilino')
            ->activos()
            ->exists();
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
     * Verificar acceso al pago
     */
    private function verificarAccesoPago($usuario, $pago): void
    {
        // El usuario que registró el pago puede verlo
        if ($pago->usuario_id === $usuario->id) {
            return;
        }

        $this->verificarAccesoInmueble($usuario, $pago->cargo->unidad->inmueble_id);
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
