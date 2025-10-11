<?php

namespace App\Jobs;

use App\Events\ImportProgressEvent;
use App\Helpers\Constants;
use App\Helpers\ErrorCollector;
use App\Models\ProcessBatch;
use App\Models\ProcessBatchesError;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Bus\Batchable;

class SaveErrorChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $customBatchId;
    protected int $offset;
    protected int $limit;
    protected string $selectedQueue;
    protected int $totalErrors;
    protected int $numChunks;
    protected string $status;

    public function __construct(string $customBatchId, int $offset, int $limit, string $selectedQueue, int $totalErrors, int $numChunks, string $status)
    {
        $this->customBatchId = $customBatchId;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->selectedQueue = $selectedQueue;
        $this->totalErrors = $totalErrors;
        $this->numChunks = $numChunks;
        $this->status = $status;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        Log::info("selectedQueue in SaveErrorChunkJob: {$this->selectedQueue}");
        try {
            $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS);
            $progressKey = "saved_errors:{$this->customBatchId}";

            // Obtener userId para notificaciones
            $metadata = $redis->hgetall("batch:{$this->customBatchId}:metadata");
            $userId = $metadata['user_id'] ?? null;

            // Fetch solo el rango de errores
            $rawErrors = $redis->lrange("import_errors:{$this->customBatchId}", $this->offset, $this->offset + $this->limit - 1);

            if (empty($rawErrors)) {
                $end = $this->offset + $this->limit - 1;
                Log::warning("No errors found in range {$this->offset} to {$end} for batch {$this->customBatchId}");
                return;
            }

            $errors = array_map(function ($errorJson) {
                return json_decode($errorJson, true);
            }, $rawErrors);

            $errorRecords = array_map(function ($error) {
                return [
                    'id' => Str::uuid(),
                    'batch_id' => $this->customBatchId,
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

            // Insertar en DB
            ProcessBatchesError::insert($errorRecords);
            $inserted = count($errorRecords);

            Log::info("Inserted chunk offset {$this->offset} with {$inserted} records for batch {$this->customBatchId}.");

            // Actualizar contador de progreso
            $processed = (int) $redis->hincrby($progressKey, 'processed', $inserted);

            // Calcular chunk actual y progreso
            $chunkNumber = ($this->offset / $this->limit) + 1;
            $progressPercent = $this->totalErrors > 0 ? round(($processed / $this->totalErrors) * 100, 2) : 0;

            // Disparar evento de progreso
            event(new ImportProgressEvent(
                $this->customBatchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                "Guardando errores: Chunk {$chunkNumber}/{$this->numChunks}", // Chunk actual/total
                (string) $this->totalErrors,
                'active',
                "{$progressPercent}%" // Progreso en porcentaje
            ));



            if ($processed >= $this->totalErrors) {
                // Actualizar estado final
                ProcessBatch::where('batch_id', $this->customBatchId)->update([
                    'error_count' => $this->totalErrors,
                    'status' => $this->status,
                    'metadata' => json_encode($metadata),
                    'updated_at' => now(),
                ]);
                $redis->hmset("batch:{$this->customBatchId}:metadata", $metadata); 
                ErrorCollector::clear($this->customBatchId);

                // Evento final
                event(new ImportProgressEvent(
                    $this->customBatchId,
                    "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                    'Guardado de errores completado',
                    (string) $this->totalErrors,
                    $this->status,
                    '100.00%' // Progreso
                ));

                // Notificar al usuario
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->notify(new BellNotification([
                            'title' => 'Validación Completada',
                            'subtitle' => "Se encontraron {$this->totalErrors} errores en el batch.",
                            'type' => 'error'
                        ]));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en SaveErrorChunkJob para batch {$this->customBatchId}, offset {$this->offset}: {$e->getMessage()}");
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new BellNotification([
                        'title' => 'Error al Guardar Chunk de Errores',
                        'subtitle' => "Fallo en chunk {$chunkNumber}: {$e->getMessage()}",
                        'type' => 'error'
                    ]));
                }
            }
            throw $e;
        }
    }
}
