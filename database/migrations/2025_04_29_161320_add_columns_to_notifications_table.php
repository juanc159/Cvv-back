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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notifiable_id', 36)->change();  // UUID tiene 36 caracteres
            $table->boolean('is_removed')->default(false); // false por defecto, lo que indica que no está eliminada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->bigInteger('notifiable_id')->change();
            $table->dropColumn('is_removed');
        });
    }
};
