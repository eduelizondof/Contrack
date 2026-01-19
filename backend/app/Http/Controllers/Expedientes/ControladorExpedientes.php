<?php

namespace App\Http\Controllers\Expedientes;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Models\InmuebleUsuario;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorExpedientes extends Controller
{
    /**
     * Listar expedientes
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Expediente::with(['usuario', 'verificador']);

        // Filtrar por usuario específico
        if ($request->filled('usuario_id')) {
            // Verificar que tenga acceso al usuario
            $this->verificarAccesoUsuario($usuario, $request->usuario_id);
            $query->where('usuario_id', $request->usuario_id);
        } else {
            // Mostrar expedientes de usuarios en inmuebles accesibles
            $usuarioIds = $this->obtenerUsuariosAccesibles($usuario);
            $query->whereIn('usuario_id', $usuarioIds);
        }

        // Filtros
        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        if ($request->has('verificado')) {
            $request->boolean('verificado') ? $query->verificados() : $query->pendientesVerificacion();
        }

        if ($request->has('vigentes')) {
            $request->boolean('vigentes') ? $query->vigentes() : $query->vencidos();
        }

        $expedientes = $query->orderByDesc('creado_el')->get();

        return response()->json([
            'datos' => $expedientes,
        ]);
    }

    /**
     * Mostrar un expediente específico
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $expediente = Expediente::with(['usuario', 'verificador'])->findOrFail($id);
        $this->verificarAccesoUsuario($request->user(), $expediente->usuario_id);

        return response()->json([
            'datos' => $expediente,
        ]);
    }

    /**
     * Crear un nuevo expediente
     */
    public function almacenar(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'tipo_documento' => 'required|string|in:INE,comprobante_domicilio,contrato,aval,acta_nacimiento,curp,rfc,otro',
            'nombre_archivo' => 'required|string|max:255',
            'ruta_archivo' => 'required|string',
            'fecha_vigencia' => 'nullable|date',
            'notas' => 'nullable|string',
        ]);

        $this->verificarAccesoUsuario($request->user(), $validados['usuario_id'], true);

        $validados['verificado'] = false;
        $expediente = Expediente::create($validados);

        return response()->json([
            'mensaje' => 'Expediente creado exitosamente',
            'datos' => $expediente->load(['usuario']),
        ], 201);
    }

    /**
     * Actualizar un expediente
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $expediente = Expediente::findOrFail($id);
        $this->verificarAccesoUsuario($request->user(), $expediente->usuario_id, true);

        $validados = $request->validate([
            'tipo_documento' => 'sometimes|string|in:INE,comprobante_domicilio,contrato,aval,acta_nacimiento,curp,rfc,otro',
            'nombre_archivo' => 'sometimes|string|max:255',
            'ruta_archivo' => 'sometimes|string',
            'fecha_vigencia' => 'nullable|date',
            'notas' => 'nullable|string',
        ]);

        $expediente->update($validados);

        return response()->json([
            'mensaje' => 'Expediente actualizado exitosamente',
            'datos' => $expediente->fresh()->load(['usuario']),
        ]);
    }

    /**
     * Eliminar un expediente
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $expediente = Expediente::findOrFail($id);
        $this->verificarAccesoUsuario($request->user(), $expediente->usuario_id, true);

        $expediente->delete();

        return response()->json([
            'mensaje' => 'Expediente eliminado exitosamente',
        ], 200);
    }

    /**
     * Verificar un expediente
     */
    public function verificar(Request $request, int $id): JsonResponse
    {
        $expediente = Expediente::findOrFail($id);
        $this->verificarAccesoUsuario($request->user(), $expediente->usuario_id, true);

        $expediente->update([
            'verificado' => true,
            'verificado_por' => $request->user()->id,
            'verificado_at' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Expediente verificado exitosamente',
            'datos' => $expediente->fresh()->load(['usuario', 'verificador']),
        ]);
    }

    /**
     * Obtener expedientes por usuario
     */
    public function porUsuario(Request $request, int $usuarioId): JsonResponse
    {
        $this->verificarAccesoUsuario($request->user(), $usuarioId);

        $expedientes = Expediente::with('verificador')
            ->where('usuario_id', $usuarioId)
            ->orderByDesc('creado_el')
            ->get();

        // Agrupar por tipo
        $agrupados = $expedientes->groupBy('tipo_documento');

        return response()->json([
            'datos' => [
                'expedientes' => $expedientes,
                'por_tipo' => $agrupados,
            ],
        ]);
    }

    /**
     * Obtener IDs de usuarios accesibles
     */
    private function obtenerUsuariosAccesibles($usuario): \Illuminate\Support\Collection
    {
        if ($usuario->hasRole('super-admin')) {
            return User::pluck('id');
        }

        $inmuebleIds = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->whereIn('rol', ['administrador', 'supervisor'])
            ->activos()
            ->pluck('inmueble_id');

        return InmuebleUsuario::whereIn('inmueble_id', $inmuebleIds)
            ->activos()
            ->pluck('usuario_id')
            ->unique();
    }

    /**
     * Verificar acceso al usuario
     */
    private function verificarAccesoUsuario($usuario, int $usuarioId, bool $requiereAdmin = false): void
    {
        // El usuario siempre puede ver sus propios expedientes
        if ($usuario->id === $usuarioId && !$requiereAdmin) {
            return;
        }

        if ($usuario->hasRole('super-admin')) {
            return;
        }

        // Verificar que el usuario sea administrador de algún inmueble donde esté el usuario objetivo
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
