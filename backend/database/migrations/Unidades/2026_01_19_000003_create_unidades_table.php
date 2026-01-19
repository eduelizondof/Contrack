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
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inmueble_id')->constrained('inmuebles')->cascadeOnDelete();
            $table->string('identificador'); // numero, letra o combinación
            $table->string('tipo'); // casa, departamento, local, bodega
            $table->string('nivel')->nullable(); // planta baja, piso 1, etc (NULL para casas)
            $table->decimal('area_m2', 10, 2)->nullable();
            $table->integer('recamaras')->nullable();
            $table->decimal('banos', 3, 1)->nullable(); // 1.5, 2, etc
            $table->integer('estacionamientos')->default(0);
            $table->json('caracteristicas')->nullable(); // balcón, jardín, roof garden, etc
            $table->auditable();

            // Índice único compuesto
            $table->unique(['inmueble_id', 'identificador']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
