<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UsuarioPruebaSeeder extends Seeder
{
    /**
     * Ejecutar el seeder para crear usuario de prueba
     */
    public function run(): void
    {
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  Creando Usuario de Prueba');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->newLine();

        // Obtener o verificar que existe el rol Administrador
        $rolAdministrador = Role::where('name', 'Administrador')->first();
        
        if (!$rolAdministrador) {
            $this->command->error('❌ Error: El rol Administrador no existe.');
            $this->command->warn('   Asegúrate de ejecutar RolesPermisosSeeder primero.');
            return;
        }

        // Verificar si el usuario ya existe
        $usuarioExistente = User::where('email', 'admin@admin.com')->first();

        if ($usuarioExistente) {
            $this->command->warn('⚠️  El usuario admin@admin.com ya existe.');
            $this->command->info('   ID: ' . $usuarioExistente->id);
            $this->command->info('   Nombre: ' . $usuarioExistente->name);
            $this->command->newLine();
            
            if ($this->command->confirm('¿Deseas actualizar la contraseña del usuario existente?', false)) {
                $usuarioExistente->password = Hash::make('admin123');
                $usuarioExistente->save();
                $this->command->info('✅ Contraseña actualizada correctamente.');
            } else {
                $this->command->info('ℹ️  Se mantuvo el usuario existente sin cambios.');
            }
            
            // Asignar rol Administrador si no lo tiene
            if (!$usuarioExistente->hasRole('Administrador')) {
                $usuarioExistente->assignRole('Administrador');
                $this->command->info('✅ Rol Administrador asignado al usuario existente.');
            } else {
                $this->command->info('ℹ️  El usuario ya tiene el rol Administrador.');
            }
        } else {
            // Crear nuevo usuario
            $usuario = User::create([
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]);

            // Asignar rol Administrador
            $usuario->assignRole('Administrador');

            $this->command->info('✅ Usuario creado exitosamente:');
            $this->command->info('   ID: ' . $usuario->id);
            $this->command->info('   Nombre: ' . $usuario->name);
            $this->command->info('   Email: ' . $usuario->email);
            $this->command->info('   Rol: Administrador (con todos los permisos)');
        }

        $this->command->newLine();
        $this->command->warn('⚠️  IMPORTANTE: Seguridad');
        $this->command->warn('   Esta es una contraseña de PRUEBA y debe ser cambiada');
        $this->command->warn('   en un entorno de producción.');
        $this->command->newLine();
        $this->command->info('📋 Credenciales de acceso:');
        $this->command->info('   Email: admin@admin.com');
        $this->command->info('   Contraseña: admin123');
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
    }
}
