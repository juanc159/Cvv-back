<?php

namespace App\Helpers;

use App\Models\ProcessBatch;
use App\Models\ProcessBatchesError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ErrorCollector
{
    /**
     * Agrega un error a la lista en Redis.
     */
    public static function addError(
        string $batchId,
        int $rowNumber,
        ?string $columnName,
        string $errorMessage,
        string $errorType,
        $errorValue,
        ?string $originalData
    ): void {
        $error = [
            'id' => Str::uuid(),
            'batch_id' => $batchId,
            'row_number' => $rowNumber,
            'column_name' => $columnName,
            'error_message' => $errorMessage,
            'error_type' => $errorType,
            'error_value' => is_null($errorValue) ? null : strval($errorValue),
            'original_data' => $originalData ?: null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        Redis::connection(Constants::REDIS_PORT_TO_IMPORTS)->rpush("import_errors:{$batchId}", json_encode($error));
        Redis::connection(Constants::REDIS_PORT_TO_IMPORTS)->expire("import_errors:{$batchId}", 3600);
    }

    /**
     * Devuelve todos los errores recolectados.
     */
    public static function getErrors(string $batchId): array
    {
        $rawErrors = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS)->lrange("import_errors:{$batchId}", 0, -1);
        $errors = [];
        foreach ($rawErrors as $errorJson) {
            $errors[] = json_decode($errorJson, true);
        }

        return $errors;
    }

    /**
     * Devuelve la cantidad de errores recolectados.
     */
    public static function countErrors(string $batchId): int
    {
        return (int) Redis::connection(Constants::REDIS_PORT_TO_IMPORTS)->llen("import_errors:{$batchId}");
    }

    /**
     * Limpia la lista de errores en Redis.
     */
    public static function clear(string $batchId): void
    {
        Redis::connection(Constants::REDIS_PORT_TO_IMPORTS)->del("import_errors:{$batchId}");
    }

    /**
     * Guarda los errores en la base de datos y actualiza ProcessBatch.
     */
    public static function saveErrorsToDatabase(string $batchId, string $status = 'failed'): void
    {
        $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS);
        $errors = self::getErrors($batchId);
        $metadata = $redis->hgetall("batch:{$batchId}:metadata");
        $metadata['completed_at'] = now()->toDateTimeString();

        if (empty($errors)) {
            // Log::info("No errors to save for batch {$batchId}");
            ProcessBatch::where('batch_id', $batchId)->update([
                'error_count' => 0,
                'status' => 'completed',
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);
            $redis->hmset("batch:{$batchId}:metadata", $metadata); 
            self::clear($batchId);
            return;
        }

        $chunkSize = 500; // Configurable: adjust to 100 or other value as needed
        $errorRecords = array_map(function ($error) use ($batchId) {
            return [
                'id' => Str::uuid(),
                'batch_id' => $batchId,
                'row_number' => $error['row_number'] ?? null,
                'column_name' => $error['column_name'] ?? null,
                'error_message' => $error['error_message'],
                'error_type' => $error['error_type'] ?? 'R',
                'error_value' => $error['error_value'] ?? null,
                'original_data' => isset($error['original_data']) ? json_encode(['data' => $error['original_data']]) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $errors);

        // Insert errors in chunks
        $totalErrors = count($errorRecords);
        $chunks = array_chunk($errorRecords, $chunkSize);
        // Log::info("Inserting {$totalErrors} errors for batch {$batchId} in " . count($chunks) . " chunks of {$chunkSize}.");

        foreach ($chunks as $index => $chunk) {
            try {
                ProcessBatchesError::insert($chunk);
                // Log::info("Inserted chunk " . ($index + 1) . " of " . count($chunks) . " for batch {$batchId} (" . count($chunk) . " records).");
            } catch (\Exception $e) {
                Log::error("Failed to insert chunk " . ($index + 1) . " for batch {$batchId}: {$e->getMessage()}");
                // Optionally notify user here if needed
                throw $e; // Re-throw to ensure the error is logged in the failed_jobs table
            }
        }

        ProcessBatch::where('batch_id', $batchId)->update([
            'error_count' => $totalErrors,
            'status' => $status,
            'metadata' => json_encode($metadata),
            'updated_at' => now(),
        ]);

        $redis->hmset("batch:{$batchId}:metadata", $metadata);

        // Log::info("Saved {$totalErrors} errors to process_batches_errors for batch {$batchId}");

        self::clear($batchId);
    }
}
