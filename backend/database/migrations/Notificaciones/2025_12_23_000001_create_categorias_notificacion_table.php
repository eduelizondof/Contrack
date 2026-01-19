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
        Schema::create('categorias_notificacion', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['proyecto', 'venta', 'inventario', 'ticket', 'chat', 'crm', 'sistema'])->unique();
            $table->string('nombre', 100);
            $table->string('icono', 50)->comment('Nombre del icono para UI');
            $table->string('color', 7)->comment('Color hex para UI');
            $table->auditable();
            
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_notificacion');
    }
};
