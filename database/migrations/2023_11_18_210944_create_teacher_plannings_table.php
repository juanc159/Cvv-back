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
        Schema::create('teacher_plannings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->nullable()->constrained('teachers');


            $table->foreignId('grade_id')->nullable()->constrained('grades');
            $table->foreignId('section_id')->nullable()->constrained('sections');
            $table->foreignId('subject_id')->nullable()->constrained('subjects');
            $table->string('path')->nullable();
            $table->string('name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_plannings');
    }
};
