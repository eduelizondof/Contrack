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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('cargos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('metodo_pago'); // transferencia, efectivo, tarjeta, pasarela
            $table->string('referencia')->nullable();
            $table->decimal('monto', 12, 2);
            $table->timestamp('fecha_pago')->useCurrent();
            $table->string('comprobante_url')->nullable();
            $table->string('estatus')->default('pendiente_verificacion'); // pagado, pendiente_verificacion, rechazado, revertido
            $table->text('notas')->nullable();
            $table->foreignId('procesado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->auditable();

            // Índices para búsquedas frecuentes
            $table->index('cargo_id');
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
