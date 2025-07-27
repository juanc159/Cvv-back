<?php

namespace App\Http\Controllers;

use App\Events\ImportProgressEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class WebSocketController extends Controller
{
    /**
     * Obtener el progreso actual desde Redis/Cache
     */
    public function getProgress($batchId)
    {
        try {
            Log::info("📊 [WEBSOCKET] Getting progress for batch: {$batchId}");
            
            // Intentar obtener desde Redis primero
            $progressData = ImportProgressEvent::getProgressFromRedis($batchId);
            
            if (!$progressData) {
                // Fallback a Cache si Redis no tiene datos
                $progressData = Cache::get("batch_progress_{$batchId}");
            }
            
            if (!$progressData) {
                // Verificar si el batch existe
                $batch = Bus::findBatch($batchId);
                if (!$batch) {
                    Log::warning("❌ [WEBSOCKET] Batch not found: {$batchId}");
                    return response()->json(['error' => 'Batch not found'], 404);
                }
                
                // Retornar estado inicial
                return response()->json([
                    'batch_id' => $batchId,
                    'progress' => 0,
                    'current_student' => 'Iniciando...',
                    'current_action' => 'Preparando importación',
                    'metadata' => [
                        'general_progress' => 0,
                        'processed_records' => 0,
                        'total_records' => 0,
                        'connection_type' => 'websocket'
                    ],
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
            
            Log::info("✅ [WEBSOCKET] Progress data found for batch: {$batchId}");
            return response()->json($progressData);
            
        } catch (\Exception $e) {
            Log::error("❌ [WEBSOCKET] Error getting progress for {$batchId}: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error retrieving progress',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar el estado de la conexión WebSocket
     */
    public function checkConnection()
    {
        try {
            Log::info("🔍 [WEBSOCKET] Checking connection status");
            
            // Verificar Redis
            Redis::ping();
            
            // Verificar si Reverb está corriendo - TIMEOUT AUMENTADO A 3 SEGUNDOS
            $reverbHost = config('broadcasting.connections.reverb.options.host', 'localhost');
            $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
            
            $connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 3); // 🔧 CAMBIADO DE 1 A 3 SEGUNDOS
            $reverbStatus = $connection ? 'connected' : 'disconnected';
            
            if ($connection) {
                fclose($connection);
            }
            
            Log::info("✅ [WEBSOCKET] Connection check completed - Reverb: {$reverbStatus}");
            
            return response()->json([
                'redis' => 'connected',
                'reverb' => $reverbStatus,
                'websocket_available' => $reverbStatus === 'connected',
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error("❌ [WEBSOCKET] Connection check failed: " . $e->getMessage());
            
            return response()->json([
                'redis' => 'error',
                'reverb' => 'unknown',
                'websocket_available' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }
    
    /**
     * Limpiar datos de progreso
     */
    public function cleanupProgress($batchId)
    {
        try {
            Log::info("🧹 [WEBSOCKET] Cleaning up progress for batch: {$batchId}");
            
            // Limpiar Redis
            Redis::del("websocket_progress_{$batchId}");
            
            // Limpiar Cache
            Cache::forget("batch_progress_{$batchId}");
            Cache::forget("batch_processed_{$batchId}");
            
            Log::info("✅ [WEBSOCKET] Progress data cleaned up for batch: {$batchId}");
            
            return response()->json([
                'message' => 'Progress data cleaned up successfully',
                'batch_id' => $batchId,
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error("❌ [WEBSOCKET] Error cleaning up progress for {$batchId}: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error cleaning up progress',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Diagnóstico completo del WebSocket
     */
    public function diagnostics()
    {
        try {
            Log::info("🔥🔥🔥 [DIAGNOSTICS] Iniciando diagnóstico completo");
            
            // Verificar Redis
            $redisStatus = 'disconnected';
            try {
                Redis::ping();
                $redisStatus = 'connected';
                Log::info("✅ [DIAGNOSTICS] Redis: OK");
            } catch (\Exception $e) {
                Log::error("❌ [DIAGNOSTICS] Redis error: " . $e->getMessage());
            }
            
            // Verificar Reverb
            $reverbHost = config('broadcasting.connections.reverb.options.host', 'localhost');
            $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
            
            $connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 5);
            $reverbStatus = $connection ? 'connected' : 'disconnected';
            
            if ($connection) {
                fclose($connection);
                Log::info("✅ [DIAGNOSTICS] Reverb: OK en {$reverbHost}:{$reverbPort}");
            } else {
                Log::error("❌ [DIAGNOSTICS] Reverb: FAIL en {$reverbHost}:{$reverbPort} - {$errstr}");
            }
            
            // Verificar configuración de broadcasting
            $broadcastDriver = config('broadcasting.default');
            $broadcastConfig = config("broadcasting.connections.{$broadcastDriver}");
            
            Log::info("🔍 [DIAGNOSTICS] Broadcast driver: {$broadcastDriver}");
            Log::info("🔍 [DIAGNOSTICS] Broadcast config:", $broadcastConfig);
            
            // Verificar procesos activos
            $activeProcesses = [];
            try {
                $keys = Redis::keys("websocket_progress_*");
                foreach ($keys as $key) {
                    $data = Redis::get($key);
                    if ($data) {
                        $processData = json_decode($data, true);
                        $activeProcesses[] = [
                            'batch_id' => $processData['batch_id'] ?? 'unknown',
                            'progress' => $processData['metadata']['general_progress'] ?? 0,
                            'timestamp' => $processData['timestamp'] ?? 'unknown'
                        ];
                    }
                }
                Log::info("📊 [DIAGNOSTICS] Procesos activos encontrados: " . count($activeProcesses));
            } catch (\Exception $e) {
                Log::error("❌ [DIAGNOSTICS] Error obteniendo procesos: " . $e->getMessage());
            }
            
            $diagnostics = [
                'redis_status' => $redisStatus,
                'reverb_status' => $reverbStatus,
                'reverb_host' => $reverbHost,
                'reverb_port' => $reverbPort,
                'broadcast_driver' => $broadcastDriver,
                'broadcast_config' => $broadcastConfig,
                'active_processes' => $activeProcesses,
                'websocket_available' => $redisStatus === 'connected' && $reverbStatus === 'connected',
                'timestamp' => now()->toDateTimeString()
            ];
            
            Log::info("🔥 [DIAGNOSTICS] Diagnóstico completo:", $diagnostics);
            
            return response()->json($diagnostics);
            
        } catch (\Exception $e) {
            Log::error("💥 [DIAGNOSTICS] Error en diagnóstico: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error en diagnóstico',
                'message' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }
}
