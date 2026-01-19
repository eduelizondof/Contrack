<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\SolicitudCrearRol;
use App\Http\Requests\Configuracion\SolicitudActualizarRol;
use App\Http\Resources\Configuracion\RecursoRol;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ControladorRoles extends Controller
{
    /**
     * Listar todos los roles
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        
        // Calcular usuarios por rol manualmente (Spatie no soporta withCount en morphToMany)
        $roles->each(function ($rol) {
            $rol->usuarios_count = User::role($rol->name)->count();
        });

        return response()->json([
            'datos' => RecursoRol::collection($roles),
        ]);
    }

    /**
     * Mostrar un rol específico
     */
    public function mostrar($id): JsonResponse
    {
        $rol = Role::with('permissions')->findOrFail($id);
        
        // Calcular usuarios con este rol
        $rol->usuarios_count = User::role($rol->name)->count();

        return response()->json([
            'datos' => new RecursoRol($rol),
        ]);
    }

    /**
     * Crear un nuevo rol
     */
    public function almacenar(SolicitudCrearRol $request): JsonResponse
    {
        $rol = Role::create([
            'name' => $request->nombre,
            'guard_name' => 'web',
        ]);

        // Asignar permisos si se proporcionaron
        if ($request->has('permisos')) {
            $rol->syncPermissions($request->permisos);
        }

        $rol->load('permissions');

        return response()->json([
            'mensaje' => 'Rol creado exitosamente',
            'datos' => new RecursoRol($rol),
        ], 201);
    }

    /**
     * Actualizar un rol existente
     */
    public function actualizar(SolicitudActualizarRol $request, $id): JsonResponse
    {
        $rol = Role::findOrFail($id);

        if ($request->has('nombre')) {
            $rol->name = $request->nombre;
            $rol->save();
        }

        // Actualizar permisos si se proporcionaron
        if ($request->has('permisos')) {
            $rol->syncPermissions($request->permisos);
        }

        $rol->load('permissions');

        return response()->json([
            'mensaje' => 'Rol actualizado exitosamente',
            'datos' => new RecursoRol($rol),
        ]);
    }

    /**
     * Eliminar un rol (verificando que no tenga usuarios asignados)
     */
    public function eliminar($id): JsonResponse
    {
        $rol = Role::findOrFail($id);
        
        // Verificar si hay usuarios con este rol
        $usuariosConRol = User::role($rol->name)->count();
        
        if ($usuariosConRol > 0) {
            $usuarios = User::role($rol->name)->take(5)->pluck('name')->toArray();
            $nombresUsuarios = implode(', ', $usuarios);
            $masUsuarios = $usuariosConRol > 5 ? " y " . ($usuariosConRol - 5) . " más" : "";
            
            return response()->json([
                'mensaje' => "No se puede eliminar el rol. Actualmente los usuarios {$nombresUsuarios}{$masUsuarios} tienen asignado este rol.",
                'usuarios_count' => $usuariosConRol,
            ], 422);
        }

        $rol->delete();

        return response()->json([
            'mensaje' => 'Rol eliminado exitosamente',
        ], 204);
    }

    /**
     * Asignar permisos a un rol
     */
    public function asignarPermisos($id): JsonResponse
    {
        $rol = Role::findOrFail($id);
        
        request()->validate([
            'permisos' => 'required|array',
            'permisos.*' => 'string|exists:permissions,name',
        ]);

        $rol->syncPermissions(request()->permisos);
        $rol->load('permissions');

        return response()->json([
            'mensaje' => 'Permisos asignados exitosamente',
            'datos' => new RecursoRol($rol),
        ]);
    }

    /**
     * Obtener permisos organizados por categorías
     */
    public function obtenerPermisosCategorias(): JsonResponse
    {
        $permisosConfig = config('permissions');
        $categorias = [];

        foreach ($permisosConfig as $modulo => $acciones) {
            $permisosCategoría = [];
            
            foreach ($acciones as $accion => $recursos) {
                foreach ($recursos as $recurso) {
                    $permisosCategoría[] = [
                        'nombre' => "{$modulo}.{$accion}.{$recurso}",
                        'accion' => $accion,
                        'recurso' => $recurso,
                    ];
                }
            }
            
            $categorias[] = [
                'modulo' => $modulo,
                'titulo' => ucfirst($modulo),
                'permisos' => $permisosCategoría,
            ];
        }

        return response()->json([
            'datos' => $categorias,
        ]);
    }
}
