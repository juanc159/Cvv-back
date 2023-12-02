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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('type_education_id')->nullable()->constrained('type_education');
            $table->foreignId('grade_id')->nullable()->constrained('grades');
            $table->foreignId('section_id')->nullable()->constrained('sections');
            $table->string("identity_document");
            $table->string("full_name");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
