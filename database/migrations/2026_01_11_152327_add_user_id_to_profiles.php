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
        // 1. Conectar Docentes
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->nullable() // Nullable al principio para poder migrar los datos después
                ->after('id')
                ->constrained('users')
                ->onDelete('cascade');
        });

        // 2. Conectar Estudiantes
        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->nullable() // Nullable al principio
                ->after('id')
                ->constrained('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
