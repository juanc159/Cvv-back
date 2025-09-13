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
        Schema::create('pending_registration_files', function (Blueprint $table) {
            $table->uuid('id')->primary(); 
            $table->foreignUuid('pending_registration_id')->constrained();
            $table->foreignUuid('subject_id')->nullable()->constrained();
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
        Schema::dropIfExists('pending_registration_files');
    }
};
