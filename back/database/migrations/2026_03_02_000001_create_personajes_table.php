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
        Schema::create('personajes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('estilo_alegre')->nullable();
            $table->string('estilo_pensando')->nullable();
            $table->string('estilo_confundido')->nullable();
            $table->string('estilo_celebrando')->nullable();
            $table->string('estilo_triste')->nullable();
            $table->string('estilo_motivado')->nullable();
            $table->string('estilo_cansado')->nullable();
            $table->string('estilo_tierno')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personajes');
    }
};
