<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue entre un alumno que se retiró y uno que terminó su último año.
 *
 * Los dos casos terminaban igual: sin grado ni sección, indistinguibles de un alumno que
 * simplemente todavía no se ha inscrito. Egresar no es lo mismo que irse del colegio, y al
 * cerrar el año escolar hay que poder separar las tres situaciones.
 *
 * Se reutiliza student_withdrawals en lugar de crear otra tabla porque el módulo de bajas
 * ya está montado sobre ella y ambos casos comparten exactamente los mismos datos:
 * quién, cuándo y por qué dejó de estar inscrito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_withdrawals', function (Blueprint $table) {
            $table->string('type', 20)->default('withdrawal')->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_withdrawals', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
