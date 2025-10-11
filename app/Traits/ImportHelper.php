<?php

namespace App\Traits;

use App\Models\ProcessBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ImportHelper
{
    protected float $benchmarkStartTime;

    protected int $benchmarkStartMemory;

    protected int $startQueries;

    protected string $currentBatchId;

    protected int $totalRowsForJobProgress; // Añadido para mantener el total de filas del archivo original para el cálculo de progreso global

    protected function startBenchmark(string $batchId): void
    {
        $this->benchmarkStartTime = microtime(true);
        $this->benchmarkStartMemory = memory_get_usage(); // Corregido: memory_usage() a memory_get_usage()
        DB::enableQueryLog();
        $this->startQueries = DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value;
    }

    protected function endBenchmark(string $batchId): void
    {
        $processBatch = ProcessBatch::where('batch_id', $batchId)->first();
        $executionTime = microtime(true) - $this->benchmarkStartTime;
        $memoryUsage = round((memory_get_usage() - $this->benchmarkStartMemory) / 1024 / 1024, 2);
        $queriesCount = DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value - (isset($this->startQueries) ? $this->startQueries : 0) - 1;

        $formattedTime = match (true) {
            $executionTime >= 60 => sprintf('%dm %ds', floor($executionTime / 60), $executionTime % 60),
            $executionTime >= 1 => round($executionTime, 2) . 's',
            default => round($executionTime * 1000) . 'ms',
        };

        // Registrar las métricas en el log (funcionalidad original)
        Log::info(sprintf(
            '⚡ Batch %s | TIME: %s | MEM: %sMB | SQL: %s | ROWS: %s',
            $batchId, // Cambiado de $this->currentBatchId a $batchId para consistencia
            $formattedTime,
            $memoryUsage,
            number_format($queriesCount),
            number_format($processBatch->total_records)
        ));

        // Obtener el metadata actual
        $existingMetadata = $processBatch->metadata;

        // Decodificar el metadata existente (si existe) o usar un array vacío
        $metadata = $existingMetadata && json_last_error() === JSON_ERROR_NONE ? json_decode($existingMetadata, true) : [];

        // Agregar las nuevas métricas bajo una clave específica
        $metadata['performance'] = [
            'time' => $formattedTime,
            'memory_mb' => $memoryUsage,
            'sql_queries' => $queriesCount,
        ];

        // Actualizar el campo metadata con los datos combinados
        $processBatch->update([
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
