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
            // IMPORTANTE: Cerrar la sesión para liberar el lock de sesión
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Configurar headers para Server-Sent Events
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Cache-Control');
            header('X-Accel-Buffering: no'); // Para Nginx
            
            // NUEVO: Headers para evitar timeouts
            header('X-Accel-Buffering: no');
            ignore_user_abort(false); // Detectar desconexiones del cliente
            set_time_limit(0); // Sin límite de tiempo
            
            // Deshabilitar el buffering de salida completamente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Función para enviar datos
            $sendData = function($data, $event = 'message') {
                echo "event: {$event}\n";
                echo "data: " . json_encode($data) . "\n\n";
                
                // Forzar envío inmediato
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    flush();
                }
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
            
            $maxTime = 300; // REDUCIDO: 5 minutos máximo (era 10)
            $startTime = time();
            $lastProgress = -1;
            $lastHeartbeat = time();
            $heartbeatInterval = 15; // REDUCIDO: Heartbeat cada 15 segundos (era 30)
            $noProgressCount = 0; // Contador para detectar procesos estancados
            
            Log::info("[SSE] Stream started for batch: {$batchId}");
            
            while (time() - $startTime < $maxTime) {
                // IMPORTANTE: Verificar si el cliente se desconectó
                if (connection_aborted()) {
                    Log::info("[SSE] Client disconnected for batch: {$batchId}");
                    break;
                }
                
                // Verificar si el batch fue cancelado o no existe
                $batch = Bus::findBatch($batchId);
                if (!$batch) {
                    $sendData([
                        'type' => 'error',
                        'batch_id' => $batchId,
                        'message' => 'Batch no encontrado',
                        'timestamp' => now()->toDateTimeString()
                    ], 'error');
                    
                    Log::warning("[SSE] Batch not found, closing stream: {$batchId}");
                    break;
                }
                
                if ($batch->cancelled()) {
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
                    
                    // Detectar si el progreso no avanza (proceso estancado)
                    if ($currentProgress === $lastProgress) {
                        $noProgressCount++;
                    } else {
                        $noProgressCount = 0;
                    }
                    
                    // Si no hay progreso por mucho tiempo, cerrar conexión
                    if ($noProgressCount > 20) { // 20 * 0.5s = 10 segundos sin progreso
                        Log::warning("[SSE] No progress detected for {$batchId}, closing connection");
                        $sendData([
                            'type' => 'timeout',
                            'batch_id' => $batchId,
                            'message' => 'Proceso sin actividad, reconectando...',
                            'timestamp' => now()->toDateTimeString()
                        ], 'timeout');
                        break;
                    }
                    
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
                
                // IMPORTANTE: Espera más corta para liberar recursos más frecuentemente
                usleep(500000); // 0.5 segundos (sin cambios)
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
