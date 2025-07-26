<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ImportCompletedEvent implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public string $batchId,
        public string $message,
        public array $stats = []
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('import.progress');
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'completed',
            'batch_id' => $this->batchId,
            'message' => $this->message,
            'stats' => $this->stats,
            'timestamp' => now()->toISOString()
        ];
    }
}