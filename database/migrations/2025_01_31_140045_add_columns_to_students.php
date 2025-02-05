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
            $table->date("birthday")->nullable();
            $table->foreignId("country_id")->nullable()->constrained("countries");
            $table->foreignId("state_id")->nullable()->constrained("states");
            $table->foreignId("city_id")->nullable()->constrained("cities");
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
            $table->dropConstrainedForeignId("country_id");
            $table->dropConstrainedForeignId("state_id");
            $table->dropConstrainedForeignId("city_id");
        });
    }
};
