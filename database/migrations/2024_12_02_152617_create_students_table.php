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
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->constrained('companies');
            $table->foreignUuid('type_education_id')->nullable()->constrained('type_education');
            $table->foreignUuid('grade_id')->nullable()->constrained('grades');
            $table->foreignUuid('section_id')->nullable()->constrained('sections');
            $table->string('identity_document');
            $table->string('full_name');
            $table->boolean('pdf')->nullable()->default(0);
            $table->longText('photo')->nullable();
            $table->string('password')->nullable();
            $table->boolean('first_time')->default(true);
            $table->boolean('is_active')->default(true);
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
