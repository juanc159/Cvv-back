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
        Schema::table('comments', function (Blueprint $table) {
            // Por defecto es interno (true) por seguridad, o false según tu lógica de negocio.
            // Vamos a ponerlo false (público) por defecto, o true si prefieres que el usuario decida explícitamente compartirlo.
            $table->boolean('is_internal')->default(false)->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
