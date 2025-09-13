<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SimpleTest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message = 'Test message')
    {
        $this->message = $message;
        \Log::info('SimpleTest: Constructor called', ['message' => $message]);
    }

    public function broadcastOn()
    {
        \Log::info('SimpleTest: broadcastOn called');
        return new Channel('test-channel');
    }

    public function broadcastAs()
    {
        \Log::info('SimpleTest: broadcastAs called');
        return 'SimpleTest';
    }

    public function broadcastWith()
    {
        \Log::info('SimpleTest: broadcastWith called');
        return ['message' => $this->message];
    }
}