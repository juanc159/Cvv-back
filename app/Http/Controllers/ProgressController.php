<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressController extends Controller
{
    public function streamProgress($batchId)
    {
        Log::info("[SSE] Starting stream for batch: {$batchId}");
        
        return new StreamedResponse(function() use ($batchId) {
            // Configurar headers para Server-Sent Events
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Cache-Control');
            header('X-Accel-Buffering: no'); // Para Nginx
            
            // Deshabilitar el buffering de salida
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Función para enviar datos
            $sendData = function($data, $event = 'message') {
                echo "event: {$event}\n";
                echo "data: " . json_encode($data) . "\n\n";
                
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            
            // Función para enviar heartbeat
            $sendHeartbeat = function() use ($sendData) {
                $sendData([
                    'type' => 'heartbeat',
                    'timestamp' => now()->toDateTimeString()
                ], 'heartbeat');
            };
            
            // Enviar mensaje inicial
            $sendData([
                'type' => 'connected',
                'batch_id' => $batchId,
                'timestamp' => now()->toDateTimeString()
            ], 'open');
            
            $maxTime = 600; // 10 minutos máximo
            $startTime = time();
            $lastProgress = -1;
            $lastHeartbeat = time();
            $heartbeatInterval = 30; // Heartbeat cada 30 segundos
            
            Log::info("[SSE] Stream started for batch: {$batchId}");
            
            while (time() - $startTime < $maxTime) {
                // Verificar si la conexión sigue activa
                if (connection_aborted()) {
                    Log::info("[SSE] Client disconnected for batch: {$batchId}");
                    break;
                }
                
                // Verificar si el batch fue cancelado
                $batch = Bus::findBatch($batchId);
                if ($batch && $batch->cancelled()) {
                    $sendData([
                        'type' => 'cancelled',
                        'batch_id' => $batchId,
                        'message' => 'El proceso fue cancelado',
                        'timestamp' => now()->toDateTimeString()
                    ], 'cancelled');
                    
                    Log::info("[SSE] Batch cancelled, closing stream: {$batchId}");
                    break;
                }
                
                // Obtener el progreso actual desde cache
                $cacheKey = "batch_progress_{$batchId}";
                $progressData = Cache::get($cacheKey);
                
                if ($progressData) {
                    $currentProgress = $progressData['metadata']['general_progress'] ?? 0;
                    
                    // Solo enviar si hay cambios significativos
                    if (abs($currentProgress - $lastProgress) >= 1 || $currentProgress >= 100) {
                        $progressData['type'] = 'progress';
                        $sendData($progressData, 'progress');
                        $lastProgress = $currentProgress;
                        
                        Log::debug("[SSE] Sent progress update: {$currentProgress}% for batch: {$batchId}");
                        
                        // Si llegamos al 100%, enviar mensaje final y terminar
                        if ($currentProgress >= 100) {
                            $sendData([
                                'type' => 'completed',
                                'batch_id' => $batchId,
                                'final_progress' => $progressData,
                                'timestamp' => now()->toDateTimeString()
                            ], 'completed');
                            
                            Log::info("[SSE] Process completed for batch: {$batchId}");
                            break;
                        }
                    }
                }
                
                // Enviar heartbeat periódicamente
                if (time() - $lastHeartbeat >= $heartbeatInterval) {
                    $sendHeartbeat();
                    $lastHeartbeat = time();
                }
                
                // Esperar antes de la siguiente verificación
                usleep(500000); // 0.5 segundos
            }
            
            // Limpiar el cache al finalizar
            Cache::forget("batch_progress_{$batchId}");
            
            // Enviar mensaje de cierre
            $sendData([
                'type' => 'closed',
                'batch_id' => $batchId,
                'timestamp' => now()->toDateTimeString()
            ], 'close');
            
            Log::info("[SSE] Stream ended for batch: {$batchId}");
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'X-Accel-Buffering' => 'no'
        ]);
    }
}
