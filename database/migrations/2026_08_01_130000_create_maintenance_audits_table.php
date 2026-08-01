<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el resultado de cada corrida de auditoría del módulo de Mantenimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tipo de auditoría (por ahora 'files'). Deja lugar a las que vengan después.
            $table->string('type')->index();

            $table->string('company_id')->nullable();
            $table->string('user_id')->nullable();

            // pending | running | completed | failed
            $table->string('status')->default('pending')->index();

            // Totales para mostrar sin abrir el detalle.
            $table->json('summary')->nullable();

            // Detalle (huérfanos, referencias rotas, agrupado por carpeta).
            $table->longText('findings')->nullable();

            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_audits');
    }
};
