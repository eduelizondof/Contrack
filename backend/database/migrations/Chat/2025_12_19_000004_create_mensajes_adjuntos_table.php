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
        Schema::create('mensajes_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mensaje_id');
            $table->enum('tipo', ['imagen', 'archivo', 'documento'])->default('archivo');
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('peso')->default(0);
            $table->timestamp('creado_el')->useCurrent();

            $table->foreign('mensaje_id')->references('id')->on('mensajes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes_adjuntos');
    }
};
