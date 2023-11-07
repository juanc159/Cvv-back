<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**6
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_complementaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers');
            $table->foreignId('grade_id')->nullable()->constrained('grades');
            $table->foreignId('section_id')->nullable()->constrained('sections');
            $table->string('subject_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_complementaries');
    }
};
