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
        Schema::create('comment_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('comment_id')->constrained()->cascadeOnDelete();

            $table->string('file_name'); // Nombre original (ej: "evidencia_pared.jpg")
            $table->string('file_path'); // Ruta en storage (ej: "comments/uuid/file.jpg")
            $table->string('mime_type')->nullable(); // image/jpeg, application/pdf
            $table->integer('size')->nullable(); // en KB

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_attachments');
    }
};
