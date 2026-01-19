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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversacion_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('responde_a_id')->nullable();
            $table->enum('tipo', ['texto', 'imagen', 'archivo', 'link'])->default('texto');
            $table->text('contenido')->nullable();
            $table->boolean('editado')->default(false);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_el')->useCurrent();
            $table->timestamp('actualizado_el')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('conversacion_id')->references('id')->on('conversaciones')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('responde_a_id')->references('id')->on('mensajes')->onDelete('set null');

            $table->index(['conversacion_id', 'creado_el']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
