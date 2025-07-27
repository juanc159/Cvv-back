<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $message;

    public function __construct()
    {
        $this->message = 'Hello from Reverb!';
    }

    public function broadcastOn()
    {
        return new Channel('test-channel');
    }
}
