<?php

namespace App\Http\Controllers\Unidades;

use App\Http\Controllers\Controller;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorInmuebleUsuarios extends Controller
{
    /**
     * Listar asignaciones de usuarios a inmuebles
     */
    public function index(Request $request): JsonResponse
    {
        $query = InmuebleUsuario::with(['inmueble', 'usuario', 'unidad']);

        // Filtrar por inmueble
        if ($request->filled('inmueble_id')) {
            $this->verificarAccesoInmueble($request->user(), $request->inmueble_id);
            $query->where('inmueble_id', $request->inmueble_id);
        } else {
            // Solo mostrar de inmuebles accesibles
            $inmuebleIds = $this->obtenerInmueblesAccesibles($request->user());
            $query->whereIn('inmueble_id', $inmuebleIds);
        }

        // Filtros
        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('unidad_id')) {
            $query->where('unidad_id', $request->unidad_id);
        }

        $asignaciones = $query->orderByDesc('fecha_inicio')->get();

        return response()->json([
            'datos' => $asignaciones,
        ]);
    }

    /**
     * Obtener usuarios de un inmueble específico
     */
    public function porInmueble(Request $request, int $inmuebleId): JsonResponse
    {
        $this->verificarAccesoInmueble($request->user(), $inmuebleId);

        $asignaciones = InmuebleUsuario::with(['usuario', 'unidad'])
            ->where('inmueble_id', $inmuebleId)
            ->activos()
            ->get()
            ->groupBy('rol');

        return response()->json([
            'datos' => $asignaciones,
        ]);
    }

    /**
     * Obtener usuarios de una unidad específica
     */
    public function porUnidad(Request $request, int $unidadId): JsonResponse
    {
        $unidad = Unidad::findOrFail($unidadId);
        $this->verificarAccesoInmueble($request->user(), $unidad->inmueble_id);

        $asignaciones = InmuebleUsuario::with(['usuario'])
            ->where('unidad_id', $unidadId)
            ->activos()
            ->get();

        return response()->json([
            'datos' => $asignaciones,
        ]);
    }

    /**
     * Asignar usuario a un inmueble/unidad
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'inmueble_id' => 'required|exists:inmuebles,id',
            'usuario_id' => 'required|exists:users,id',
            'rol' => 'required|string|in:propietario,inquilino,administrador,tecnico,vigilancia',
            'unidad_id' => 'nullable|exists:unidades,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'activo' => 'nullable|boolean',
        ]);

        $this->verificarAccesoInmueble($request->user(), $validados['inmueble_id'], 'administrador');

        // Verificar que la unidad pertenezca al inmueble
        if (!empty($validados['unidad_id'])) {
            $unidad = Unidad::findOrFail($validados['unidad_id']);
            if ($unidad->inmueble_id != $validados['inmueble_id']) {
                return response()->json([
                    'mensaje' => 'La unidad no pertenece al inmueble especificado',
                ], 422);
            }
        }

        // Verificar si ya existe una asignación activa con el mismo rol
        $existente = InmuebleUsuario::where('inmueble_id', $validados['inmueble_id'])
            ->where('usuario_id', $validados['usuario_id'])
            ->where('rol', $validados['rol'])
            ->when(!empty($validados['unidad_id']), fn ($q) => $q->where('unidad_id', $validados['unidad_id']))
            ->activos()
            ->first();

        if ($existente) {
            return response()->json([
                'mensaje' => 'El usuario ya tiene una asignación activa con ese rol',
            ], 422);
        }

        $validados['activo'] = $validados['activo'] ?? true;
        $asignacion = InmuebleUsuario::create($validados);

        return response()->json([
            'mensaje' => 'Usuario asignado exitosamente',
            'datos' => $asignacion->load(['inmueble', 'usuario', 'unidad']),
        ], 201);
    }

    /**
     * Actualizar asignación de usuario
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $asignacion = InmuebleUsuario::findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $asignacion->inmueble_id, 'administrador');

        $validados = $request->validate([
            'rol' => 'sometimes|string|in:propietario,inquilino,administrador,tecnico,vigilancia',
            'unidad_id' => 'nullable|exists:unidades,id',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'activo' => 'nullable|boolean',
        ]);

        // Verificar que la unidad pertenezca al inmueble
        if (!empty($validados['unidad_id'])) {
            $unidad = Unidad::findOrFail($validados['unidad_id']);
            if ($unidad->inmueble_id != $asignacion->inmueble_id) {
                return response()->json([
                    'mensaje' => 'La unidad no pertenece al inmueble de la asignación',
                ], 422);
            }
        }

        $asignacion->update($validados);

        return response()->json([
            'mensaje' => 'Asignación actualizada exitosamente',
            'datos' => $asignacion->fresh()->load(['inmueble', 'usuario', 'unidad']),
        ]);
    }

    /**
     * Eliminar (desactivar) asignación de usuario
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $asignacion = InmuebleUsuario::findOrFail($id);
        $this->verificarAccesoInmueble($request->user(), $asignacion->inmueble_id, 'administrador');

        // En lugar de eliminar, desactivar
        $asignacion->update([
            'activo' => false,
            'fecha_fin' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Asignación desactivada exitosamente',
        ], 200);
    }

    /**
     * Obtener usuarios disponibles para asignar a un inmueble
     */
    public function usuariosDisponibles(Request $request, int $inmuebleId): JsonResponse
    {
        $this->verificarAccesoInmueble($request->user(), $inmuebleId, 'administrador');

        // Usuarios que NO tienen asignación activa en este inmueble
        $usuariosAsignados = InmuebleUsuario::where('inmueble_id', $inmuebleId)
            ->activos()
            ->pluck('usuario_id');

        $usuarios = User::whereNotIn('id', $usuariosAsignados)
            ->where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'datos' => $usuarios,
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
