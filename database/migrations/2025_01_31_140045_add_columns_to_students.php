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
        Schema::table('students', function (Blueprint $table) {
            $table->string("gender")->nullable();
            $table->string("birthday")->nullable();
            $table->foreignId("country_id")->nullable();
            $table->foreignId("state_id")->nullable();
            $table->foreignId("city_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn("gender");
            $table->dropColumn("birthday");
            $table->dropColumn("country_id");
            $table->dropColumn("state_id");
            $table->dropColumn("city_id");
        });
    }
};
