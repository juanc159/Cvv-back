<?php

namespace App\Jobs\Student;

use App\Events\ImportProgressEvent;
use App\Helpers\Constants;
use App\Helpers\ErrorCollector;
use App\Helpers\ExcelDataStudentValidator;
use App\Helpers\ExcelRequired;
use App\Models\ProcessBatch;
use App\Models\Student; // Agregar import del modelo Student
use App\Models\User;
use App\Notifications\BellNotification;
use App\Traits\ImportHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Bus\Batchable;

class ImportStudentExcelJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ImportHelper;

    protected string $customBatchId;
    protected string $userId;
    protected string $selectedQueue;
    protected array $metadata;

    public function __construct(
        string $customBatchId,
        string $selectedQueue,
    ) {
        $this->customBatchId   = $customBatchId;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {


        $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS);
        $this->metadata = $redis->hgetall("batch:{$this->customBatchId}:metadata");
        $this->userId = $this->metadata['user_id'] ?? null;
        $companyId = $this->metadata['company_id'] ?? null; // Obtener company_id para upserts

        $xlsCollection = ExcelRequired::openXls($this->metadata['filePath']);
        Log::info("xlsCollection", ['count' => $xlsCollection->count()]);
        Log::info("xlsCollection", [$xlsCollection]);

        $metadata = $this->metadata;
        $metadata['total_rows'] = $xlsCollection->count();
        $redis->hmset("batch:{$this->customBatchId}:metadata", $metadata);

        // Evento inicial (opcional, como en tu código original)
        event(new ImportProgressEvent(
            $this->customBatchId,
            '0',
            "Iniciando validación de datos del Excel...",
            '0',
            'active',
            "Leyendo archivo Excel..."
        ));

        $processed = 0;
        $validCount = 0;
        $hasErrors = false;
        $validRowsKey = "batch:{$this->customBatchId}:valid_rows"; // Key para lista en Redis

        // Limpiar key si existe (por si reintento)
        $redis->del($validRowsKey);

        $this->startBenchmark($this->customBatchId);

        try {
            foreach ($xlsCollection as $rowIndex => $row) {
                // Validar esta fila (igual)
                $rowHasError = ExcelDataStudentValidator::validateRow($this->customBatchId, $row, $rowIndex);
                if ($rowHasError) {
                    $hasErrors = true;
                } else {
                    // Si válido, agregar a Redis: limpiar keys vacías primero
                    $cleanRow = array_filter($row, function ($key) {
                        return !empty(trim($key)); // Filtra keys vacías o con solo espacios
                    }, ARRAY_FILTER_USE_KEY);
                    $cleanRow['company_id'] = $companyId; // Agregar company_id
                    $redis->rpush($validRowsKey, json_encode($cleanRow));
                    $validCount++;
                    Log::info("Cleaned row pushed to Redis", ['row_keys' => array_keys($cleanRow)]); // Debug opcional
                }

                $processed++;

                // Evento después de cada validación (adaptado a params del evento)
                event(new ImportProgressEvent(
                    $this->customBatchId,
                    (string) $processed,  // processedRecords
                    "Validando datos del registro",  // currentAction
                    (string) ErrorCollector::countErrors($this->customBatchId),  // errorCount
                    'active',  // backendStatus
                    "Fila {$processed}"  // currentElement
                ));
            }

            // Ahora, upsert masivo en chunks de 500 
            if ($validCount > 0) {
                $validCollection = collect($redis->lrange($validRowsKey, 0, -1))
                    ->map(function ($json) {
                        return json_decode($json, true);
                    });

                $chunkSize = 2; // Configurable
                $chunkedUpserts = $validCollection->chunk($chunkSize);

                Log::info("Starting upsert of {$validCount} valid students in chunks of {$chunkSize} for batch {$this->customBatchId}");
                Log::info("validCollection", [$validCollection]);
                Log::info("chunkedUpserts", [$chunkedUpserts]);

                foreach ($chunkedUpserts as $chunk) {
                    // Preparar data para upsert: formatear fechas
                    $insertData = $chunk->map(function ($row) {
                        // Remover keys vacías
                        $row = array_filter($row, function ($key) {
                            return !empty(trim($key));
                        }, ARRAY_FILTER_USE_KEY);

                        // Default para nationalized: 0 si vacío/null
                        if (!isset($row['nationalized']) || $row['nationalized'] === '' || $row['nationalized'] === null) {
                            $row['nationalized'] = 0;
                        }
 
                        // Formatear fechas si aplican
                        if (isset($row['birthday']) && $row['birthday']) {
                            $row['birthday'] = Carbon::parse($row['birthday'])->format('Y-m-d');
                        }
                        if (isset($row['real_entry_date']) && $row['real_entry_date']) {
                            $row['real_entry_date'] = Carbon::parse($row['real_entry_date'])->format('Y-m-d');
                        }
                        return $row;
                    })->values()->toArray();

                    Log::info("insertData", [$insertData]);


                    // Campos únicos para upsert (identity_document + company_id)
                    $uniqueBy = ['identity_document', 'company_id'];

                    // Campos a actualizar: todos excepto 'id' (de la primera row)
                    $firstRowKeys = array_keys($insertData[0] ?? []);
                    $updateColumns = array_values(array_diff($firstRowKeys, ['id']));


                    Log::info("uniqueBy", [$uniqueBy]);
                    Log::info("updateColumns", [$updateColumns]);



                    // Upsert por chunk
                    Student::upsert(
                        $insertData,
                        $uniqueBy,
                        $updateColumns
                    );
                }

                Log::info("Upserted {$validCount} students (insert/update) for batch {$this->customBatchId}");
            }

            // Limpiar Redis
            $redis->del($validRowsKey);

            // Actualizar metadata con valid_count
            $metadata['valid_count'] = $validCount;
            $redis->hmset("batch:{$this->customBatchId}:metadata", $metadata);

            // Al final, actualizar batch
            $finalStatus = $hasErrors ? 'completed_with_errors' : 'completed';
            ProcessBatch::where('batch_id', $this->customBatchId)->update([
                'total_records' => $processed,
                'processed_records' => $processed,
                'error_count' => ErrorCollector::countErrors($this->customBatchId),
                'status' => $finalStatus,
                'metadata' => json_encode($this->metadata),
                'updated_at' => now(),
            ]);

            // Notificaciones ajustadas
            if ($hasErrors) {
                $msg = "Validación completada con errores. Upserted (insert/update) {$validCount} registros válidos de {$processed} basados en identity_document. Revisa el reporte.";
                $this->notifyUser($this->userId, 'Importación completada con errores', $msg, 'warning');
            } else {
                $this->notifyUser($this->userId, 'Importación completada exitosamente', "Se upserted (insert/update) {$validCount} registros de estudiantes correctamente basados en identity_document.", 'success');
            }

            // Evento final (opcional)
            event(new ImportProgressEvent(
                $this->customBatchId,
                (string) $processed,
                "Validación e importación completada",
                (string) ErrorCollector::countErrors($this->customBatchId),
                $finalStatus,
                "Proceso finalizado (válidos: {$validCount})"
            ));

            $this->endBenchmark($this->customBatchId);
        } catch (\Throwable $e) {
            // Limpiar Redis en error
            $redis->del($validRowsKey);
            Log::error("Error en ImportStudentExcelJob: {$e->getMessage()}", [
                'customBatchId' => $this->customBatchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->updateBatchStatus('failed');
            $this->notifyUser($this->userId, 'Error en validación e importación del Excel', "Error: {$e->getMessage()}", 'error');
            $this->fail($e);
        }
    }

    /**
     * Actualiza el estado del batch en la tabla process_batches.
     *
     * @param string $status
     * @return void
     */
    protected function updateBatchStatus(string $status): void
    {
        ProcessBatch::where('batch_id', $this->customBatchId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    /**
     * Envía una notificación al usuario.
     *
     * @param string|null $userId
     * @param string $title
     * @param string $message
     * @param string $type
     * @return void
     */
    protected function notifyUser(?string $userId, string $title, string $message, string $type): void
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new BellNotification([
                    'title' => $title,
                    'subtitle' => $message,
                    'type' => $type,
                ]));
            } else {
                Log::warning("Usuario no encontrado para notificación: {$userId}");
            }
        } else {
            Log::warning("No se proporcionó userId para notificación en batch {$this->customBatchId}");
        }
    }
}
