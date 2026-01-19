<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inmueble_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inmueble_id')->constrained('inmuebles')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('rol'); // propietario, inquilino, administrador, tecnico, vigilancia
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->auditable();

            // Índice para búsquedas frecuentes
            $table->index(['inmueble_id', 'usuario_id', 'rol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmueble_usuarios');
    }
};
