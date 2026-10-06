<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Establece la cardinalidad estricta Persona 0..1 <-> 0..1 User en la base de datos MySQL.
     * En MySQL, UNIQUE sobre una columna NULL permite múltiples registros técnicos con NULL
     * pero prohíbe que más de una cuenta User comparta la misma Persona (persona_id).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('persona_id', 'users_persona_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_persona_id_unique');
        });
    }
};
