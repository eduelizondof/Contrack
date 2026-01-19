<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeder de roles y permisos primero
        $this->call([
            RolesPermisosSeeder::class,
            UsuarioPruebaSeeder::class,
            CategoriaNotificacionSeeder::class
        ]);
    }
}
