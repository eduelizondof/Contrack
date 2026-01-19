<?php

namespace App\Http\Controllers\Inmuebles;

use App\Http\Controllers\Controller;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorInmuebles extends Controller
{
    /**
     * Listar inmuebles accesibles para el usuario
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Inmueble::query();

        // Filtrar por inmuebles accesibles según rol
        if (!$usuario->hasRole('super-admin')) {
            $inmuebleIds = InmuebleUsuario::where('usuario_id', $usuario->id)
                ->activos()
                ->pluck('inmueble_id');
            $query->whereIn('id', $inmuebleIds);
        }

        // Filtros opcionales
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('ciudad', 'like', "%{$buscar}%")
                    ->orWhere('direccion', 'like', "%{$buscar}%");
            });
        }

        // Incluir conteos
        $query->withCount(['unidades', 'cuentasBancarias', 'conceptos']);

        // Paginación
        $porPagina = $request->input('por_pagina', 15);
        $inmuebles = $query->orderBy('nombre')->paginate($porPagina);

        return response()->json([
            'datos' => $inmuebles->items(),
            'meta' => [
                'total' => $inmuebles->total(),
                'por_pagina' => $inmuebles->perPage(),
                'pagina_actual' => $inmuebles->currentPage(),
                'ultima_pagina' => $inmuebles->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar un inmueble específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::with(['cuentasBancarias', 'conceptos'])
            ->withCount(['unidades', 'inmuebleUsuarios'])
            ->findOrFail($id);

        // Verificar acceso
        $this->verificarAcceso($request->user(), $inmueble);

        return response()->json([
            'datos' => $inmueble,
        ]);
    }

    /**
     * Crear un nuevo inmueble
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|in:condominio,fraccionamiento,edificio',
            'direccion' => 'required|string',
            'ciudad' => 'required|string|max:255',
            'estado' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'total_unidades' => 'nullable|integer|min:0',
            'reglamento_url' => 'nullable|string|url',
            'activo' => 'nullable|boolean',
        ]);

        $inmueble = Inmueble::create($validados);

        // Asignar al usuario creador como administrador
        InmuebleUsuario::create([
            'inmueble_id' => $inmueble->id,
            'usuario_id' => $request->user()->id,
            'rol' => 'administrador',
            'fecha_inicio' => now(),
            'activo' => true,
        ]);

        return response()->json([
            'mensaje' => 'Inmueble creado exitosamente',
            'datos' => $inmueble,
        ], 201);
    }

    /**
     * Actualizar un inmueble existente
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::findOrFail($id);
        $this->verificarAcceso($request->user(), $inmueble, 'administrador');

        $validados = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|string|in:condominio,fraccionamiento,edificio',
            'direccion' => 'sometimes|string',
            'ciudad' => 'sometimes|string|max:255',
            'estado' => 'sometimes|string|max:255',
            'codigo_postal' => 'sometimes|string|max:10',
            'total_unidades' => 'nullable|integer|min:0',
            'reglamento_url' => 'nullable|string|url',
            'activo' => 'nullable|boolean',
        ]);

        $inmueble->update($validados);

        return response()->json([
            'mensaje' => 'Inmueble actualizado exitosamente',
            'datos' => $inmueble->fresh(),
        ]);
    }

    /**
     * Eliminar un inmueble (soft delete)
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::withCount('unidades')->findOrFail($id);
        $this->verificarAcceso($request->user(), $inmueble, 'administrador');

        // Verificar que no tenga unidades
        if ($inmueble->unidades_count > 0) {
            return response()->json([
                'mensaje' => 'No se puede eliminar el inmueble porque tiene unidades asociadas',
                'unidades_count' => $inmueble->unidades_count,
            ], 422);
        }

        $inmueble->delete();

        return response()->json([
            'mensaje' => 'Inmueble eliminado exitosamente',
        ], 200);
    }

    /**
     * Resumen del inmueble con estadísticas
     */
    public function resumen(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::with(['unidades', 'cuentasBancarias'])
            ->findOrFail($id);

        $this->verificarAcceso($request->user(), $inmueble);

        $unidadIds = $inmueble->unidades->pluck('id');

        // Estadísticas
        $contratosActivos = \App\Models\Contrato::whereIn('unidad_id', $unidadIds)
            ->activos()
            ->count();

        $adeudoTotal = \App\Models\Cargo::whereIn('unidad_id', $unidadIds)
            ->conAdeudo()
            ->sum('monto');

        $ticketsAbiertos = \App\Models\Ticket::whereIn('unidad_id', $unidadIds)
            ->activos()
            ->count();

        $ingresosDelMes = \App\Models\Pago::whereHas('cargo', function ($q) use ($unidadIds) {
            $q->whereIn('unidad_id', $unidadIds);
        })
            ->pagados()
            ->delMes()
            ->sum('monto');

        return response()->json([
            'datos' => [
                'inmueble' => $inmueble,
                'estadisticas' => [
                    'total_unidades' => $inmueble->unidades->count(),
                    'contratos_activos' => $contratosActivos,
                    'adeudo_total' => $adeudoTotal,
                    'tickets_abiertos' => $ticketsAbiertos,
                    'ingresos_del_mes' => $ingresosDelMes,
                ],
            ],
        ]);
    }

    /**
     * Verificar que el usuario tenga acceso al inmueble
     */
    private function verificarAcceso($usuario, $inmueble, ?string $rolMinimo = null): void
    {
        if ($usuario->hasRole('super-admin')) {
            return;
        }

        $query = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->where('inmueble_id', $inmueble->id)
            ->activos();

        if ($rolMinimo === 'administrador') {
            $query->whereIn('rol', ['administrador', 'supervisor']);
        }

        if (!$query->exists()) {
            abort(403, 'No tienes acceso a este inmueble');
        }
    }
}
