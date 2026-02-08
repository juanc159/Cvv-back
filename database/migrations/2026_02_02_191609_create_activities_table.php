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
       Schema::create('activities', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->foreignUuid('company_id')
                    ->constrained()
                    ->cascadeOnDelete();

                // Tu sistema usa tabla teachers (modelo Teacher)
                $table->foreignUuid('teacher_id')
                    ->constrained('teachers')
                    ->cascadeOnDelete();

                // Selects dependientes
                $table->foreignUuid('grade_id')
                    ->nullable()
                    ->constrained('grades')
                    ->nullOnDelete();

                $table->foreignUuid('section_id')
                    ->nullable()
                    ->constrained('sections')
                    ->nullOnDelete();

                $table->foreignUuid('subject_id')
                    ->nullable()
                    ->constrained('subjects')
                    ->nullOnDelete();

                $table->string('title');
                $table->text('description')->nullable();

                $table->timestamp('deadline_at')->nullable();

                // Guardará valores del enum: ACTIVITY_STATUS_001 / 002 / 003
                $table->string('status');

                $table->timestamps();
                $table->softDeletes();

                // Índices
                $table->index(['company_id', 'teacher_id', 'status']);
                $table->index(['company_id', 'grade_id', 'section_id', 'subject_id']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
