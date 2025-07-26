<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImportProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $batchId,
        public int $progress,
        public string $currentStudent,
        public string $currentAction,
        public array $metadata = []
    ) {
        // Asegurar estructura completa del metadata
        $this->metadata = array_merge([
            'sheet' => 0,
            'chunk' => 0,
            'current_row' => 0,
            'total_rows' => 0,
            'subjects' => [],
            'total_records' => 0,
            'processed_records' => 0,
            'general_progress' => 0,
            'cancelled' => false // NUEVO: indicador de cancelación
        ], $metadata);

        // Guardar en cache para Server-Sent Events
        $this->storeProgressInCache();
    }

    protected function storeProgressInCache()
    {
        $progressData = [
            'batch_id' => $this->batchId,
            'progress' => $this->progress,
            'current_student' => $this->currentStudent,
            'current_action' => $this->currentAction,
            'metadata' => $this->metadata,
            'timestamp' => now()->toDateTimeString()
        ];

        // Guardar en cache por 2 horas
        Cache::put("batch_progress_{$this->batchId}", $progressData, now()->addHours(2));
    }

    public function broadcastOn(): Channel
    {
        return new Channel('import.progress.' . $this->batchId);
    }

    public function broadcastAs(): string
    {
        return 'progress.update';
    }

    public function broadcastWith()
    {
        Log::debug("Enviando evento WS para batch {$this->batchId}", [
            'progress' => $this->progress,
            'student' => $this->currentStudent,
            'general_progress' => $this->metadata['general_progress'],
            'cancelled' => $this->metadata['cancelled'] ?? false
        ]);

        return [
            'batch_id' => $this->batchId,
            'progress' => $this->progress,
            'current_student' => $this->currentStudent,
            'current_action' => $this->currentAction,
            'metadata' => [
                'sheet' => $this->metadata['sheet'],
                'chunk' => $this->metadata['chunk'],
                'processed_rows' => $this->metadata['current_row'],
                'total_rows' => $this->metadata['total_rows'],
                'subjects_processed' => count($this->metadata['subjects']),
                'total_records' => $this->metadata['total_records'],
                'processed_records' => $this->metadata['processed_records'],
                'general_progress' => $this->metadata['general_progress'],
                'cancelled' => $this->metadata['cancelled'] ?? false
            ],
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
