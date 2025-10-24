<?php

namespace App\Jobs;

use App\Events\ImportProgressEvent;
use App\Helpers\Constants;
use App\Helpers\ErrorCollector;
use App\Models\ProcessBatch;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SaveErrorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $batchId;
    protected string $selectedQueue;

    public function __construct(string $batchId, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS);
        $totalErrors = ErrorCollector::countErrors($this->batchId);

        $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
        $metadata['completed_at'] = now()->toDateTimeString();
        $userId = $metadata['user_id'] ?? null;

        if ($totalErrors === 0) {
            // Log::info("No errors to save for batch {$this->batchId}");
            ProcessBatch::where('batch_id', $this->batchId)->update([
                'error_count' => 0,
                'status' => 'completed',
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);
            $redis->hmset("batch:{$this->batchId}:metadata", $metadata);
            ErrorCollector::clear($this->batchId);

            // Evento final sin errores
            event(new ImportProgressEvent(
                $this->batchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                'Validación completada',
                "0", // 0 errores
                'active',
                'Ha finalizado sin novedad' // Progreso
            ));

            // Notificar éxito
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new BellNotification([
                        'title' => 'Validación Completada',
                        'subtitle' => 'No se encontraron errores para el batch.',
                        'type' => 'success'
                    ]));
                }
            }
            return;
        }

        $status = 'failed';
        $chunkSize = 500; // Ajusta a 100 si quieres menos memoria
        $numChunks = (int) ceil($totalErrors / $chunkSize);

        // Inicializar contador de progreso
        $progressKey = "saved_errors:{$this->batchId}";
        $redis->hmset($progressKey, ['processed' => 0]);
        $redis->expire($progressKey, 3600);

        // Log::info("Iniciando guardado de {$totalErrors} errores para batch {$this->batchId} en {$numChunks} chunks.");

        // Preparar jobs para chunks
        $jobs = [];
        for ($i = 0; $i < $numChunks; $i++) {
            $offset = $i * $chunkSize;
            $jobs[] = SaveErrorChunkJob::dispatch($this->batchId, $offset, $chunkSize, $this->selectedQueue, $totalErrors, $numChunks, $status)->onQueue($this->queue);

            // Evento final sin errores
            event(new ImportProgressEvent(
                $this->batchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                "Se esta guardando los errores encontrados", // Todos los registros procesados
               $totalErrors, // Total de errores
                'active',
                "Guardando errores... ($i/$numChunks)" // Progreso
            ));
        }

    }
}
