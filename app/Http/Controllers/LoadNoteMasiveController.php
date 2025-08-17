<?php

namespace App\Http\Controllers;

use App\Events\ImportProgressEvent;
use App\Services\ExcelNoteProcessor;
use App\Services\ExcelStructureValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class LoadNoteMasiveController extends Controller
{
    public function __construct(
        protected ExcelNoteProcessor $noteProcessor,
        protected ExcelStructureValidator $structureValidator
    ) {}

    public function process(Request $request): JsonResponse
    {
        try {
            // Validar request
            $validatedData = $this->validateRequest($request);
            
            // Procesar archivo
            $uploadedFile = $this->validateAndGetFile($request);
            
            // Guardar archivo temporalmente
            $filePath = $this->storeTemporaryFile($uploadedFile);
            $fullPath = storage_path('app/public/' . $filePath);
            
            // Validar estructura del archivo
            $this->validateFileStructure($fullPath, $validatedData, $filePath);
            
            // Procesar archivo
            $result = $this->processFile($fullPath, $validatedData, $filePath);
            
            // Enviar evento de progreso inicial
            $this->dispatchInitialProgressEvent($result['batch_id']);
            
            // Retornar respuesta exitosa
            return $this->successResponse($result, $uploadedFile);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->handleException($e, $filePath ?? null);
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'archive' => 'required|file',
            'teacher_id' => 'nullable|string',
            'type_education_id' => 'nullable|string',
            'company_id' => 'nullable|string',
            'user_id' => 'nullable|string',
        ]);
    }

    private function validateAndGetFile(Request $request)
    {
        if (!$request->hasFile('archive')) {
            throw new \Exception('No se encontró el archivo en el request');
        }

        $uploadedFile = $request->file('archive');

        if (!$uploadedFile || !$uploadedFile->isValid()) {
            throw new \Exception('El archivo no es válido');
        }

        return $uploadedFile;
    }

    private function storeTemporaryFile($uploadedFile): string
    {
        $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
        return $uploadedFile->storeAs('temp', $fileName, 'public');
    }

    private function validateFileStructure(string $fullPath, array $validatedData, string $filePath): void
    {
        $validation = $this->structureValidator->validate(
            $fullPath,
            $validatedData['teacher_id'],
            $validatedData['type_education_id'],
            $validatedData['company_id']
        );

        if ($validation['operation_failed']) {
            Storage::disk('public')->delete($filePath);
            Log::warning("Validation failed for file: {$fullPath}");
            
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json([
                    'status' => 'error',
                    'message' => 'Errores en la validación',
                    'errors' => $validation['data']
                ], 422)
            );
        }

        Log::info("Validation successful, starting processing for: {$fullPath}");
    }

    private function processFile(string $fullPath, array $validatedData, string $filePath): array
    {
        $result = $this->noteProcessor->processFile(
            $fullPath,
            $validatedData['company_id'],
            $validatedData['user_id'],
            $validatedData['type_education_id'],
            $validatedData['teacher_id'],
        );

        if (!$result['success']) {
            Storage::disk('public')->delete($filePath);
            Log::error("Processing error for: {$fullPath} - {$result['error']}");
            throw new \Exception($result['error']);
        }

        return $result;
    }

    private function dispatchInitialProgressEvent(string $batchId): void
    {
        ImportProgressEvent::dispatch(
            $batchId,
            '0',
            'Iniciando proceso',
            '0',
            'queued',
            'Validando estructura'
        );
    }

    private function successResponse(array $result, $uploadedFile): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'batch_id' => $result['batch_id'],
            'sheets' => $result['total_sheets'],
            'chunks' => $result['total_chunks'],
            'total_records' => $result['total_records'],
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_size' => $result['file_size'] ?? 0,
            'processing_start_time' => $result['processing_start_time'] ?? now()->toDateTimeString(),
        ], 200);
    }

    private function validationErrorResponse(\Illuminate\Validation\ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);
    }

    private function handleException(\Exception $e, ?string $filePath): JsonResponse
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        Log::error("Exception during file processing: {$e->getMessage()}", [
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Error procesando el archivo: ' . $e->getMessage()
        ], 500);
    }
}
