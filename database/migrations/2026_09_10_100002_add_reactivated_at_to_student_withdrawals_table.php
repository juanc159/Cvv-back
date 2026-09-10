<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un alumno que se dio de baja puede volver a inscribirse el año siguiente.
 *
 * Hasta ahora el retiro era definitivo: StudentController::withdraw rechaza registrar
 * una baja si ya existe una, así que un alumno que volvía quedaba marcado como retirado
 * para siempre y no se le podía dar de baja otra vez más adelante.
 *
 * Con esta columna el retiro se puede cerrar cuando el alumno se reintegra, sin borrar
 * el registro histórico de que estuvo de baja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_withdrawals', function (Blueprint $table) {
            $table->timestamp('reactivated_at')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('student_withdrawals', function (Blueprint $table) {
            $table->dropColumn('reactivated_at');
        });
    }
};
