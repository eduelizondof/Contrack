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
        Schema::create('usuario_notificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->onDelete('cascade');
            $table->foreignId('id_notificacion')->constrained('notificaciones')->onDelete('cascade');
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_el')->nullable();
            
            $table->unique(['id_usuario', 'id_notificacion'], 'usuario_notificacion_unique');
            $table->index(['id_usuario', 'leido']);
            $table->index('id_notificacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_notificacion');
    }
};
