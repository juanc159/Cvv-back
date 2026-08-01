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
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained();


            $table->foreignUuid('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->foreignUuid('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('link')->nullable();
            $table->text('note')->nullable();

            // pending | submitted | needs_changes | approved | rejected | late
            $table->string('status')->default('pending');

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->unique(['activity_id', 'student_id']);
            $table->index(['activity_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
