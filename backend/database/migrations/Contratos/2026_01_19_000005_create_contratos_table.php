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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo'); // renta, propiedad
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // NULL para indefinidos
            $table->decimal('monto_mensual', 12, 2)->nullable(); // NULL si es propietario
            $table->unsignedTinyInteger('dia_pago')->nullable(); // día del mes para pago (1-31)
            $table->boolean('renovacion_automatica')->default(false);
            $table->string('archivo_contrato')->nullable();
            $table->string('estado')->default('activo'); // activo, finalizado, cancelado
            $table->auditable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
