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
        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inmueble_id')->constrained('inmuebles')->cascadeOnDelete();
            $table->string('nombre'); // mantenimiento, agua, cuota extraordinaria, renta, etc
            $table->text('descripcion')->nullable();
            $table->string('tipo'); // fijo, variable, extraordinario
            $table->decimal('monto_default', 12, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->auditable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos');
    }
};
