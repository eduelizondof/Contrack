<?php

namespace App\Helpers;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Helper para gestionar permisos y roles del sistema
 */
class PermissionHelper
{
    /**
     * Generar todos los permisos desde la configuración
     * 
     * @return array Array de permisos creados
     */
    public static function generarPermisosDesdeConfig(): array
    {
        $permisos = config('permissions', []);
        $permisosCreados = [];

        foreach ($permisos as $modulo => $acciones) {
            foreach ($acciones as $accion => $recursos) {
                foreach ($recursos as $recurso) {
                    $nombrePermiso = "{$modulo}.{$accion}.{$recurso}";
                    
                    $permiso = Permission::firstOrCreate(
                        ['name' => $nombrePermiso],
                        ['guard_name' => 'web']
                    );
                    
                    $permisosCreados[] = $permiso;
                }
            }
        }

        return $permisosCreados;
    }

    /**
     * Obtener permisos agrupados por módulo
     * 
     * @return array
     */
    public static function obtenerPermisosPorModulo(): array
    {
        $permisos = config('permissions', []);
        $permisosAgrupados = [];

        foreach ($permisos as $modulo => $acciones) {
            $permisosAgrupados[$modulo] = [];
            
            foreach ($acciones as $accion => $recursos) {
                foreach ($recursos as $recurso) {
                    $nombrePermiso = "{$modulo}.{$accion}.{$recurso}";
                    $permisosAgrupados[$modulo][] = $nombrePermiso;
                }
            }
        }

        return $permisosAgrupados;
    }

    /**
     * Obtener todos los nombres de permisos como array plano
     * 
     * @return array
     */
    public static function obtenerTodosLosPermisos(): array
    {
        $permisos = config('permissions', []);
        $todosLosPermisos = [];

        foreach ($permisos as $modulo => $acciones) {
            foreach ($acciones as $accion => $recursos) {
                foreach ($recursos as $recurso) {
                    $todosLosPermisos[] = "{$modulo}.{$accion}.{$recurso}";
                }
            }
        }

        return $todosLosPermisos;
    }

    /**
     * Asignar permisos a un rol
     * 
     * @param Role|string $rol Rol o nombre del rol
     * @param array $permisos Array de nombres de permisos
     * @return void
     */
    public static function asignarPermisosARol($rol, array $permisos): void
    {
        if (is_string($rol)) {
            $rol = Role::where('name', $rol)->firstOrFail();
        }

        $permisosObjetos = Permission::whereIn('name', $permisos)->get();
        $rol->syncPermissions($permisosObjetos);
    }

    /**
     * Obtener permisos de un módulo específico
     * 
     * @param string $modulo
     * @return array
     */
    public static function obtenerPermisosDeModulo(string $modulo): array
    {
        $permisos = config('permissions', []);
        
        if (!isset($permisos[$modulo])) {
            return [];
        }

        $permisosModulo = [];
        foreach ($permisos[$modulo] as $accion => $recursos) {
            foreach ($recursos as $recurso) {
                $permisosModulo[] = "{$modulo}.{$accion}.{$recurso}";
            }
        }

        return $permisosModulo;
    }

    /**
     * Obtener permisos de una acción específica en un módulo
     * 
     * @param string $modulo
     * @param string $accion
     * @return array
     */
    public static function obtenerPermisosDeAccion(string $modulo, string $accion): array
    {
        $permisos = config('permissions', []);
        
        if (!isset($permisos[$modulo][$accion])) {
            return [];
        }

        $permisosAccion = [];
        foreach ($permisos[$modulo][$accion] as $recurso) {
            $permisosAccion[] = "{$modulo}.{$accion}.{$recurso}";
        }

        return $permisosAccion;
    }
}
