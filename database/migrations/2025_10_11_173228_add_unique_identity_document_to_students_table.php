<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->unique(['identity_document', 'company_id'], 'unique_identity_per_company'); // Nombre único para el index
        });
    }

    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('unique_identity_per_company');
        });
    }
};