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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('usuario_reporta_id')->constrained('users')->cascadeOnDelete();
            $table->string('categoria'); // plomeria, electricidad, limpieza, seguridad, estructura, otro
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('prioridad')->default('media'); // baja, media, alta, urgente
            $table->string('estado')->default('abierto'); // abierto, en_proceso, pausado, resuelto, cerrado, cancelado
            $table->timestamp('fecha_reporte')->useCurrent();
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();
            $table->unsignedTinyInteger('valoracion')->nullable(); // 1-5 estrellas
            $table->text('comentario_valoracion')->nullable();
            $table->auditable();

            // Índices para búsquedas frecuentes
            $table->index('unidad_id');
            $table->index('estado');
            $table->index('prioridad');
            $table->index('fecha_reporte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
