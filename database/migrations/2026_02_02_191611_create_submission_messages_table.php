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
        Schema::create('submission_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('submission_id')
                ->constrained('submissions')
                ->cascadeOnDelete();

            $table->foreignUuid('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('message');

            $table->timestamps();

            $table->index(['submission_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_messages');
    }
};
