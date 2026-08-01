<?php

use App\Helpers\Constants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Siembra el interruptor de las constancias/certificados de prosecución.
 *
 * Arranca encendido a propósito: hoy la funcionalidad está activa, y la migración no debe
 * cambiar el comportamiento al desplegar. El administrador la apaga desde el módulo de Notas
 * cuando termine la temporada de fin de año.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('block_data')
            ->where('name', Constants::ENABLE_PROSECUTION_DOCUMENTS)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('block_data')->insert([
            'id' => (string) Str::uuid(),
            'name' => Constants::ENABLE_PROSECUTION_DOCUMENTS,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('block_data')
            ->where('name', Constants::ENABLE_PROSECUTION_DOCUMENTS)
            ->delete();
    }
};
