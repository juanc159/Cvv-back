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
        Schema::create('pending_registration_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pending_registration_student_id')->constrained()->name('pending_registration_student_id_foreign');
            $table->foreignUuid('subject_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registration_subjects');
    }
};
