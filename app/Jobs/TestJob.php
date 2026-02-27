<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected $batchId;

    public function __construct($message = 'Hola Mundo', $batchId = null)
    {
        $this->message = $message;
        $this->batchId = $batchId ?? uniqid();
        
        Log::info("TestJob CONSTRUIDO - Message: {$this->message}, Batch: {$this->batchId}");
    }

    public function handle()
    {
        Log::info("========== TESTOJOB INICIADO ==========");
        Log::info("Batch ID: " . $this->batchId);
        Log::info("Mensaje recibido: " . $this->message);
        
        // Simular proceso
        sleep(2);
        
        Log::info("Paso 1 completado");
        
        sleep(2);
        
        Log::info("Paso 2 completado");
        
        Log::info("========== TESTOJOB FINALIZADO ==========");
        Log::info("Resultado: Todo salió bien!");
    }
}