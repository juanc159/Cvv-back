<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El detalle fila por fila de un lote de promoción.
 *
 * Guarda tanto lo que venía en el archivo (en crudo, sin normalizar) como lo que el
 * sistema decidió hacer con esa fila. Así, si mañana alguien pregunta "¿por qué este
 * alumno cambió de cédula?", la respuesta está aquí y no hay que adivinarla.
 *
 * Ojo: no todas las filas vienen del archivo. Las de acción "unassign" las genera el
 * sistema para los alumnos que estaban en el grado/sección destino y que nadie reclamó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batch_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_batch_id')->constrained('promotion_batches')->cascadeOnDelete();

            // Número de fila en el Excel tal como lo ve el usuario. Null en las filas
            // que genera el sistema (los que salen de la sección).
            $table->integer('row_number')->nullable();

            // promote           -> el alumno existe y se le cambia grado/sección
            // stay              -> ya estaba en ese grado y sección, no hay nada que cambiar
            // repeat            -> vuelve al mismo grado (repitiente)
            // reinstate         -> estaba sin grado o de baja y vuelve a inscribirse
            // update_document   -> además de moverlo, se le corrige la cédula
            // create            -> alumno nuevo, no existe en el sistema
            // unassign          -> sale del grado/sección destino porque no vino en el archivo
            // error             -> la fila no se puede procesar
            $table->string('action');

            // exact         -> coincidió por cédula
            // name_birthday -> coincidió por nombre + fecha de nacimiento
            // name          -> coincidió solo por nombre
            // none          -> no coincidió con nadie
            $table->string('match_type')->nullable();

            $table->foreignUuid('student_id')->nullable()->constrained('students')->nullOnDelete();

            // Lo que traía el archivo, sin tocar.
            $table->string('identity_document_raw')->nullable();
            $table->string('identity_document_normalized')->nullable();
            $table->string('full_name_raw')->nullable();

            // Estado anterior del alumno, para poder revertir o auditar.
            $table->string('previous_identity_document')->nullable();
            $table->uuid('previous_grade_id')->nullable();
            $table->uuid('previous_section_id')->nullable();
            $table->string('previous_grade_name')->nullable();
            $table->string('previous_section_name')->nullable();

            // Avisos y errores para mostrar en la previsualización.
            $table->json('messages')->nullable();
            // El resto de columnas del archivo (sexo, nacimiento, estado, municipio, país...).
            $table->json('payload')->nullable();

            // Las acciones que requieren criterio humano (cambio de cédula, alta nueva,
            // coincidencia por nombre) llegan sin aprobar y el usuario decide en pantalla.
            $table->boolean('needs_review')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_applied')->default(false);

            $table->timestamps();

            $table->index(['promotion_batch_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batch_rows');
    }
};
