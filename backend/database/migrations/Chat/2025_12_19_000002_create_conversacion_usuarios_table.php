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
        Schema::create('conversacion_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversacion_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('es_admin')->default(false);
            $table->boolean('archivada')->default(false);
            $table->timestamp('ultimo_visto_at')->nullable();
            $table->timestamp('creado_el')->useCurrent();

            $table->foreign('conversacion_id')->references('id')->on('conversaciones')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['conversacion_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversacion_usuarios');
    }
};
