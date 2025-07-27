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
        Schema::create('type_education_note_selections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('type_education_id')->constrained('type_education')->onDelete('cascade');
            $table->integer('note_number');
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_education_note_selections');
    }
};
