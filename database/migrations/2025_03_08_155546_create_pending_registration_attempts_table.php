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
        Schema::create('pending_registration_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pending_registration_id')->constrained();
            $table->foreignUuid('student_id')->constrained();
            $table->foreignUuid('subject_id')->constrained();
            $table->tinyInteger('attempt_number')->unsigned()->comment('1 to 4');
            $table->decimal('note', 5, 2)->nullable()->comment('Nota del intento (0-20)');
            $table->date('attempt_date')->nullable()->comment('Fecha del intento');
            $table->boolean('approved')->default(false)->comment('Si la nota aprueba (e.g., >= 10)');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registration_attempts');
    }
};
