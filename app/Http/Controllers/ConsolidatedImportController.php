<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessConsolidatedImportJob;
use App\Jobs\TestJob;
use App\Services\ProcessBatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; 

class ConsolidatedImportController extends Controller
{
    public function upload(Request $request)
    { 
        $request->validate([
            'archive' => 'required|file',
            'company_id' => 'required',
            'type_education_id' => 'required',
            'teacher_id' => 'nullable',
        ]);

        try {
            // 1. Guardar archivo (Disco PUBLIC como corregimos antes)
            $file = $request->file('archive');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('temp_imports', $fileName, 'public');
            $fileSize = $file->getSize(); // Obtenemos el tamaño para metadata

            // 2. Generar Batch ID y Metadata AQUI en el controlador
            $batchId = (string) Str::uuid();
            $companyId = $request->input('company_id');
            $userId = $request->input('user_id') ?? 'system'; // Ajusta según tu auth

            $metadata = [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $fileSize,
                'started_at' => now()->toDateTimeString(),
            ];

            // 3. Crear el registro en BD (ProcessBatch)
            // Pasamos 0 en total_records por ahora. El Job lo actualizará cuando cuente las filas.
            ProcessBatchService::initProcess(
                $batchId,
                $companyId,
                $userId,
                0, // Total records temporal
                $metadata
            );

            // 4. Preparar datos para el Job
            $data = [
                'company_id' => $companyId,
                'type_education_id' => $request->input('type_education_id'),
                'teacher_id' => $request->input('teacher_id'),
                'user_id' => $userId,
            ];

            // Log::info("Despachando Job con Batch ID: {$batchId}");

            // 5. Despachar el Job pasando el batchId generado 
            ProcessConsolidatedImportJob::dispatch($filePath, $data, $batchId);

            // 6. Respuesta al Front (Con el batch_id correcto)
            return response()->json([
                'status' => 'success', // Cambiado a 'status' para coincidir con tu front
                'message' => 'Archivo recibido. Procesando...',
                'batch_id' => $batchId // ¡CRUCIAL PARA EL FRONT!
            ]);
        } catch (\Exception $e) {
            Log::error("Error Controller: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al iniciar carga: ' . $e->getMessage()
            ], 500);
        }
    }
}
