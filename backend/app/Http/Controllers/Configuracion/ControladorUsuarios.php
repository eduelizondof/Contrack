<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\SolicitudCrearUsuario;
use App\Http\Requests\Configuracion\SolicitudActualizarUsuario;
use App\Http\Requests\Configuracion\SolicitudRestablecerPassword;
use App\Http\Resources\Configuracion\RecursoUsuario;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ControladorUsuarios extends Controller
{
    /**
     * Listar usuarios con paginación opcional y filtros
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Optimizar: seleccionar solo campos necesarios y cargar roles con solo campos necesarios
        $query = User::select('id', 'name', 'email', 'activo', 'created_at')
            ->with(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name');
            }]);

        // Filtro de búsqueda por nombre o email
        if ($request->has('buscar') && $request->buscar) {
            $busqueda = $request->buscar;
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        // Filtro por rol - usar whereHas ya que es más eficiente que join para relaciones many-to-many
        if ($request->has('rol') && $request->rol) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->rol);
            });
        }

        // Filtro por activos
        if ($request->has('solo_activos') && $request->boolean('solo_activos')) {
            $query->where('activo', true);
        }

        // Ordenar por nombre
        $query->orderBy('name');

        // Si paginar=false, devolver todos los resultados sin paginar
        if ($request->has('paginar') && $request->paginar === 'false') {
            $usuarios = $query->get();
            
            return response()->json([
                'datos' => RecursoUsuario::collection($usuarios),
            ]);
        }

        // Por defecto, paginar
        $porPagina = $request->input('por_pagina', 10);
        $usuarios = $query->paginate($porPagina);

        return response()->json([
            'datos' => RecursoUsuario::collection($usuarios),
            'meta' => [
                'total' => $usuarios->total(),
                'por_pagina' => $usuarios->perPage(),
                'pagina_actual' => $usuarios->currentPage(),
                'ultima_pagina' => $usuarios->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar un usuario específico
     */
    public function mostrar($id): JsonResponse
    {
        // Optimizar: seleccionar solo campos necesarios y cargar roles con solo campos necesarios
        $usuario = User::select('id', 'name', 'email', 'activo', 'created_at')
            ->with(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name');
            }])->findOrFail($id);

        return response()->json([
            'datos' => new RecursoUsuario($usuario),
        ]);
    }

    /**
     * Crear un nuevo usuario
     */
    public function almacenar(SolicitudCrearUsuario $request): JsonResponse
    {
        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asignar rol si se proporcionó
        if ($request->has('rol_id') && $request->rol_id) {
            $usuario->syncRoles([$request->rol_id]);
        }

        $usuario->load('roles');

        return response()->json([
            'mensaje' => 'Usuario creado exitosamente',
            'datos' => new RecursoUsuario($usuario),
        ], 201);
    }

    /**
     * Actualizar datos básicos del usuario
     */
    public function actualizar(SolicitudActualizarUsuario $request, $id): JsonResponse
    {
        $usuario = User::findOrFail($id);

        $datosActualizar = [];
        
        if ($request->has('name')) {
            $datosActualizar['name'] = $request->name;
        }
        
        if ($request->has('email')) {
            $datosActualizar['email'] = $request->email;
        }

        if (!empty($datosActualizar)) {
            $usuario->update($datosActualizar);
        }

        $usuario->load('roles');

        return response()->json([
            'mensaje' => 'Usuario actualizado exitosamente',
            'datos' => new RecursoUsuario($usuario),
        ]);
    }

    /**
     * Eliminar un usuario (soft delete)
     */
    public function eliminar($id): JsonResponse
    {
        $usuario = User::findOrFail($id);
        
        // No permitir eliminar al usuario actual
        if (auth()->id() === $usuario->id) {
            return response()->json([
                'mensaje' => 'No puedes eliminar tu propia cuenta',
            ], 403);
        }

        $usuario->delete();

        return response()->json([
            'mensaje' => 'Usuario eliminado exitosamente',
        ], 204);
    }

    /**
     * Actualizar rol del usuario
     */
    public function actualizarRol(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rol_id' => 'required|exists:roles,id',
        ], [
            'rol_id.required' => 'El rol es obligatorio.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
        ]);

        $usuario = User::findOrFail($id);
        $usuario->syncRoles([$request->rol_id]);
        $usuario->load('roles');

        return response()->json([
            'mensaje' => 'Rol actualizado exitosamente',
            'datos' => new RecursoUsuario($usuario),
        ]);
    }

    /**
     * Restablecer contraseña del usuario
     */
    public function restablecerPassword(SolicitudRestablecerPassword $request, $id): JsonResponse
    {
        $usuario = User::findOrFail($id);
        
        $usuario->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'mensaje' => 'Contraseña actualizada exitosamente',
        ]);
    }
}
