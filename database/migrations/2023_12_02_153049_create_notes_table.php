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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students');
            $table->foreignId('subject_id')->nullable()->constrained('subjects');
            $table->string("value1")->nullable();
            $table->string("value2")->nullable();
            $table->string("value3")->nullable();
            $table->string("value4")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
