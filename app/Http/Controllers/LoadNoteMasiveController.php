<?php

namespace App\Http\Controllers;

use App\Events\ImportProgressEvent;
use App\Services\ExcelNoteProcessor;
use App\Services\ExcelStructureValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LoadNoteMasiveController extends Controller
{
    public function __construct(
        protected ExcelNoteProcessor $noteProcessor,
        protected ExcelStructureValidator $structureValidator
    ) {}

    public function process(Request $request)
    {
        // Log::info('🚀 [CONTROLLER] Starting file processing with WebSocket strategy');

        try {
            $request->validate([
                'archive' => 'required|file|mimes:xlsx,xls|max:10240',
                'teacher_id' => 'nullable|string',
                'type_education_id' => 'nullable|string',
                'company_id' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }

        if (!$request->hasFile('archive')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró el archivo en el request'
            ], 400);
        }

        $uploadedFile = $request->file('archive');
        
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'El archivo no es válido'
            ], 400);
        }

        // Guardar archivo temporalmente
        $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
        $filePath = $uploadedFile->storeAs('temp', $fileName, 'public');
        $fullPath = storage_path('app/public/' . $filePath);

        try {
            // Log::info("🔍 [CONTROLLER] Starting validation for: {$fileName}");
            
            // Validación rápida
            $validation = $this->structureValidator->validate(
                $fullPath,
                $request->input('teacher_id'),
                $request->input('type_education_id', '2'),
                $request->input('company_id', '1')
            );

            if ($validation['operation_failed']) {
                Storage::disk('public')->delete($filePath);
                Log::warning("❌ [CONTROLLER] Validation failed for: {$fileName}");
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Errores en la validación',
                    'errors' => $validation['data']
                ], 422);
            }

            // Log::info("✅ [CONTROLLER] Validation successful, starting processing for: {$fileName}");

            // Procesamiento asíncrono
            $result = $this->noteProcessor->processFile(
                $fullPath,
                $request->input('company_id', '1'),
                $request->input('type_education_id', '1'),
                $request->input('teacher_id')
            );

            if (!$result['success']) {
                Storage::disk('public')->delete($filePath);
                Log::error("❌ [CONTROLLER] Processing error for: {$fileName} - {$result['error']}");
                
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error']
                ], 500);
            }

            // Inicializar progreso en cache
            Cache::put("batch_processed_{$result['batch_id']}", 0, now()->addHours(2));
            
            // Log::info("🎯 [CONTROLLER] Batch created successfully: {$result['batch_id']} for file: {$fileName}");

            // ✅ EMITIR EVENTO INICIAL CON METADATA COMPLETA
            event(new ImportProgressEvent(
                $result['batch_id'],
                0,
                'Iniciando proceso',
                'Validando estructura',
                [
                    'sheet' => 0,
                    'chunk' => 0,
                    'current_row' => 0,
                    'total_rows' => 0,
                    'total_sheets' => $result['total_sheets'],
                    'total_chunks' => $result['total_chunks'],
                    'total_records' => $result['total_records'],
                    'processed_records' => 0,
                    'general_progress' => 0,
                    'connection_type' => 'websocket',
                    // ✅ NUEVOS DATOS INICIALES
                    'current_sheet' => 1,
                    'errors_count' => 0,
                    'warnings_count' => 0,
                    'file_size' => $result['file_size'] ?? 0,
                    'processing_start_time' => $result['processing_start_time'] ?? now()->toDateTimeString(),
                    'last_activity' => now()->toDateTimeString(),
                    'memory_usage' => memory_get_usage(true),
                    'cpu_usage' => 0,
                    'connection_status' => 'connected',
                ]
            ));

            // Log::info("📤 [CONTROLLER] Sending immediate response for batch: {$result['batch_id']}");
            
            return response()->json([
                'status' => 'success',
                'batch_id' => $result['batch_id'],
                'sheets' => $result['total_sheets'],
                'chunks' => $result['total_chunks'],
                'total_records' => $result['total_records'],
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_size' => $result['file_size'] ?? 0,
                'processing_start_time' => $result['processing_start_time'] ?? now()->toDateTimeString(),
                // 'message' => 'Archivo enviado a procesamiento. El progreso se actualizará via WebSocket.'
            ], 200);

        } catch (\Exception $e) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            
            Log::error("💥 [CONTROLLER] Exception during processing: {$e->getMessage()}", [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error procesando el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
