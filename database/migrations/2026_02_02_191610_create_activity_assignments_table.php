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
        Schema::create('activity_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->foreignUuid('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['activity_id', 'student_id']);
            $table->index(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_assignments');
    }
};
