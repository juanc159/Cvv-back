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
        Schema::create('activity_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');

            $table->text('comments')->nullable(); // Texto del alumno
            $table->json('links')->nullable();    // Archivos/Links del alumno

            // --- NUEVO CAMPO ---
            $table->integer('attempt_number')->default(1);
            // -------------------

            // Usamos el Enum como string por defecto
            $table->string('status')->default('submitted');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_submissions');
    }
};
