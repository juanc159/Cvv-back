<?php

namespace App\Http\Controllers;

use App\Events\ImportProgressEvent;
use App\Jobs\ProcessExcelDataJob;
use App\Services\ExcelDataProcessor;
use App\Services\ExcelNoteProcessor;
use App\Services\ExcelStructureValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class LoadNoteMasiveController extends Controller
{
    public function __construct(
        protected ExcelNoteProcessor $noteProcessor,
        protected ExcelStructureValidator $structureValidator
    ) {}

    public function process(Request $request)
    {
        // Debug: Ver qué está llegando en el request
        \Log::info('Request data:', [
            'has_file' => $request->hasFile('archive'),
            'all_files' => $request->allFiles(),
            'all_data' => $request->all(),
            'content_type' => $request->header('Content-Type')
        ]);

        // Validar que se envió un archivo
        try {
            $request->validate([
                'archive' => 'required|file|mimes:xlsx,xls|max:10240', // máximo 10MB
                'teacher_id' => 'nullable|string', // CORREGIDO: UUID
                'type_education_id' => 'nullable|string', // CORREGIDO: UUID
                'company_id' => 'nullable|string' // CORREGIDO: UUID
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors(),
                'debug' => [
                    'has_file' => $request->hasFile('archive'),
                    'files' => $request->allFiles()
                ]
            ], 422);
        }

        // Verificar si el archivo existe
        if (!$request->hasFile('archive')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró el archivo en el request',
                'debug' => [
                    'all_data' => $request->all(),
                    'files' => $request->allFiles(),
                    'content_type' => $request->header('Content-Type')
                ]
            ], 400);
        }

        // Obtener el archivo del request
        $uploadedFile = $request->file('archive');
        
        // Verificar que el archivo no sea null
        if (!$uploadedFile) {
            return response()->json([
                'status' => 'error',
                'message' => 'El archivo está vacío o no se pudo procesar'
            ], 400);
        }

        // Verificar que el archivo sea válido
        if (!$uploadedFile->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'El archivo no es válido: ' . $uploadedFile->getErrorMessage()
            ], 400);
        }

        // Guardar temporalmente el archivo
        $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
        $filePath = $uploadedFile->storeAs('temp', $fileName, 'public');
        $fullPath = storage_path('app/public/' . $filePath);

        try {
            // Validación de estructura
            $validation = $this->structureValidator->validate(
                $fullPath,
                $request->input('teacher_id'), // CORREGIDO: mantener como string
                $request->input('type_education_id', '1'), // CORREGIDO: string con default
                $request->input('company_id', '1') // CORREGIDO: string con default
            );

            if ($validation['operation_failed']) {
                // Eliminar archivo temporal si hay error
                Storage::disk('public')->delete($filePath);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Errores en la validación',
                    'errors' => $validation['data']
                ], 422);
            }
 
            // Procesamiento del archivo
            $result = $this->noteProcessor->processFile(
                $fullPath,
                $request->input('company_id', '1'), // CORREGIDO: mantener como string
                $request->input('type_education_id', '1'), // CORREGIDO: mantener como string
                $request->input('teacher_id') // CORREGIDO: mantener como string o null
            );

            // Inicializar contador de registros procesados
            Cache::put("batch_processed_{$result['batch_id']}", 0, now()->addHours(2));

            // Emitir evento inicial
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
                    'general_progress' => 0
                ]
            ));

            if (!$result['success']) {
                // Eliminar archivo temporal si hay error
                Storage::disk('public')->delete($filePath);
                
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error']
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'batch_id' => $result['batch_id'],
                'sheets' => $result['total_sheets'],
                'chunks' => $result['total_chunks'],
                'total_records' => $result['total_records'],
                'file_name' => $uploadedFile->getClientOriginalName()
            ]);

        } catch (\Exception $e) {
            // Eliminar archivo temporal en caso de excepción
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error procesando el archivo: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function checkStatus($batchId)
    {
        $batch = Bus::findBatch($batchId);
        
        if (!$batch) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $batch->finishedAt ? 'completed' : 'processing',
            'progress' => $batch->progress(),
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs
        ]);
    }
}
