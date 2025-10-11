<?php

namespace App\Jobs\Student;

use App\Events\ImportProgressEvent;
use App\Helpers\Constants;
use App\Helpers\ErrorCollector; 
use App\Helpers\ExcelRequired;
use App\Helpers\ExcelValidator;
use App\Models\ProcessBatch;
use App\Models\User;
use App\Notifications\BellNotification; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Bus\Batchable;

class ValidateExcelJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        Log::info("metadata", $this->metadata);

        $xlsCollection = ExcelRequired::openXls($this->metadata['filePath']);
        Log::info("xlsCollection", ['count' => $xlsCollection->count()]);

        $metadata = $this->metadata;
        $metadata['total_rows'] = $xlsCollection->count();
        $redis->hmset("batch:{$this->customBatchId}:metadata", $metadata);

        $errors = false;
        event(new ImportProgressEvent(
            $this->customBatchId,
            0,
            "Iniciando validación de estructura del Excel...",
            0,
            'active',
            "Leyendo archivo Excel...",
        ));

        try {

            $errors = ExcelValidator::validateAll($this->customBatchId, $xlsCollection, json_decode($this->metadata['required'], 1));

            if ($errors) {
                ProcessBatch::where('batch_id', $this->customBatchId)->update([
                    'error_count' => ErrorCollector::countErrors($this->customBatchId),
                    'status' => 'failed',
                    'metadata' => json_encode($this->metadata),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Error en ValidateExcelJob: {$e->getMessage()}", [
                'customBatchId' => $this->customBatchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->updateBatchStatus('failed');
            $this->notifyUser($this->userId, 'Error en Validacion de estructura del Excel', "Error en Validacion de estructura del Excel: {$e->getMessage()}", 'error');
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
