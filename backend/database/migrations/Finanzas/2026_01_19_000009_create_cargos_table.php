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
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->foreignId('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('conceptos')->cascadeOnDelete();
            $table->string('periodo'); // 2026-01
            $table->decimal('monto', 12, 2);
            $table->date('fecha_vencimiento');
            $table->string('estado')->default('pendiente'); // pendiente, pagado, vencido, cancelado
            $table->text('notas')->nullable();
            $table->auditable();

            // Índices para búsquedas frecuentes
            $table->index(['unidad_id', 'periodo']);
            $table->index('estado');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
