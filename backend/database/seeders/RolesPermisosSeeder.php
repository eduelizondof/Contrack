<?php

namespace Database\Seeders;

use App\Helpers\PermissionHelper;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generar todos los permisos desde la configuración
        $this->command->info('Generando permisos desde configuración...');
        PermissionHelper::generarPermisosDesdeConfig();
        $this->command->info('Permisos generados correctamente.');

        // Obtener todos los permisos
        $todosLosPermisos = PermissionHelper::obtenerTodosLosPermisos();

        // Crear rol Administrador con todos los permisos
        $administrador = Role::firstOrCreate(
            ['name' => 'Administrador'],
            ['guard_name' => 'web']
        );
        PermissionHelper::asignarPermisosARol($administrador, $todosLosPermisos);
        $this->command->info('Rol Administrador creado con todos los permisos.');

        // Crear rol Gerente con ver y editar en todos los módulos (sin eliminar)
        $gerente = Role::firstOrCreate(
            ['name' => 'Gerente'],
            ['guard_name' => 'web']
        );
        $permisosGerente = [];
        foreach ($todosLosPermisos as $permiso) {
            // Incluir todos los permisos excepto eliminar
            if (!str_contains($permiso, '.eliminar.')) {
                $permisosGerente[] = $permiso;
            }
        }
        PermissionHelper::asignarPermisosARol($gerente, $permisosGerente);
        $this->command->info('Rol Gerente creado con permisos de ver y editar.');

        $this->command->info('Seeder de roles y permisos completado exitosamente.');
    }
}
