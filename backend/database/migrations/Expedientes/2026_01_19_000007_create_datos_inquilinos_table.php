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
        Schema::create('datos_inquilinos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('ocupacion')->nullable();
            $table->string('empresa')->nullable();
            $table->string('referencia_nombre')->nullable();
            $table->string('referencia_telefono')->nullable();
            $table->string('contacto_emergencia_nombre')->nullable();
            $table->string('contacto_emergencia_telefono')->nullable();
            $table->unsignedTinyInteger('numero_personas_habitan')->nullable();
            $table->boolean('tiene_mascotas')->default(false);
            $table->string('tipo_mascotas')->nullable();
            $table->timestamp('creado_el')->useCurrent();
            $table->timestamp('actualizado_el')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datos_inquilinos');
    }
};
