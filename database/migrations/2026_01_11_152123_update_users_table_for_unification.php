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
        Schema::table('users', function (Blueprint $table) {
            // 1. Agregamos el campo para que el estudiante inicie sesión
            // Lo hacemos nullable porque los admin/profes quizas no lo usen para login
            $table->string('identity_document')->nullable()->unique()->after('email');

            // 2. Tipo de usuario para saber "quién es" rápidamente
            // Le ponemos 'admin' por defecto para los usuarios que ya existen en esta tabla
            $table->string('type_user')->default('admin')->after('role_id');

            // 3. IMPORTANTÍSIMO: Hacemos el email nullable.
            // ¿Por qué? Porque si creamos un usuario estudiante solo con Cédula,
            // el sistema nos daría error si exige email obligatorio.
            $table->string('email')->nullable()->change();

            // 4. Hacemos el role_id nullable, porque quizás un estudiante básico no tiene rol de Spatie aun
            $table->foreignUuid('role_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('identity_document');
            $table->dropColumn('type_user');
            // Revertir cambios (cuidado al hacer rollback si hay datos nulos)
            $table->string('email')->nullable(false)->change();
        });
    }
};
