<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lotes de promoción: un registro por cada archivo que se sube para mover
 * alumnos de un año escolar al siguiente.
 *
 * Antes esto se hacía editando a mano la ruta /actualizar de web.php, así que no
 * quedaba ni rastro de qué se movió, cuándo ni quién lo hizo. Con esta tabla cada
 * carga queda sellada contra el período escolar (terms) y se puede auditar después.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('company_id')->constrained('companies');
            $table->foreignUuid('term_id')->constrained('terms');
            $table->uuid('user_id')->nullable();

            // Destino elegido en pantalla. Todas las filas del archivo van aquí.
            $table->foreignUuid('type_education_id')->constrained('type_education');
            $table->foreignUuid('grade_id')->constrained('grades');
            $table->foreignUuid('section_id')->constrained('sections');

            $table->string('file_name')->nullable();

            // preview: ya se analizó pero no se ha aplicado nada.
            // applied: confirmado y escrito en students.
            // discarded: el usuario lo descartó sin aplicar.
            $table->string('status')->default('preview');

            // Fecha de ingreso a usar para los alumnos que se creen desde este archivo.
            $table->date('entry_date')->nullable();

            $table->integer('total_rows')->default(0);
            $table->integer('applied_rows')->default(0);
            $table->json('summary')->nullable();

            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'term_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batches');
    }
};
