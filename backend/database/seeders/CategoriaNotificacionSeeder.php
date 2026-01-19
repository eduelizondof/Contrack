<?php

namespace Database\Seeders;

use App\Models\Notificaciones\CategoriaNotificacion;
use Illuminate\Database\Seeder;

class CategoriaNotificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'tipo' => 'chat',
                'nombre' => 'Chat',
                'icono' => 'message-circle',
                'color' => '#8b5cf6',
            ],
            [
                'tipo' => 'sistema',
                'nombre' => 'Sistema',
                'icono' => 'bell',
                'color' => '#6b7280',
            ],
        ];

        foreach ($categorias as $categoria) {
            CategoriaNotificacion::firstOrCreate(
                ['tipo' => $categoria['tipo']],
                $categoria
            );
        }
    }
}
