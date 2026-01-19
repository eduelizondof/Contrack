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
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->boolean('es_grupo')->default(true);
            $table->unsignedBigInteger('creado_por');
            $table->timestamp('creado_el')->useCurrent();
            $table->timestamp('actualizado_el')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('eliminado_el')->nullable();

            $table->foreign('creado_por')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversaciones');
    }
};
