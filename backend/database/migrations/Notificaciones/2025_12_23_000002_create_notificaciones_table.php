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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_categoria')->constrained('categorias_notificacion')->onDelete('restrict');
            $table->string('tipo', 100)->comment('Identificador único del tipo, ej: proyecto.deadline_vencido');
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->json('datos_json')->nullable()->comment('Datos adicionales para la notificación');
            $table->enum('accion_tipo', ['ruta', 'url', 'ninguna'])->default('ninguna');
            $table->string('accion_ruta', 500)->nullable()->comment('Ruta de la app, ej: /proyectos/123');
            $table->json('accion_parametros')->nullable()->comment('Parámetros adicionales para la acción');
            $table->foreignId('id_usuario_creador')->nullable()->constrained('users')->onDelete('set null');
            $table->auditable();
            
            $table->index('tipo');
            $table->index('prioridad');
            $table->index('id_categoria');
            $table->index('creado_el');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
