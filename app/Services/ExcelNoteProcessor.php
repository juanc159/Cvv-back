<?php

namespace App\Services;

use App\Jobs\ProcessNoteChunkJob;
use App\Models\{
    Student,
    Note,
    Teacher,
    TypeEducation,
    Grade,
    Section,
    Subject
};
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExcelNoteProcessor
{
    protected $chunkSize = 20;

    public function processFile(
        string $filePath,
        string $companyId,
        string $typeEducationId,
        ?string $teacherId = null
    ): array {
        try {
            Log::info("📊 [PROCESSOR] Iniciando procesamiento de archivo: {$filePath}");
            
            $sheets = Excel::toArray([], $filePath);
            $batchJobs = [];
            
            // PASO 1: Calcular el total de registros ANTES de crear los jobs
            $totalRecords = 0;
            foreach ($sheets as $sheet) {
                $dataRows = array_slice($sheet, 1); // Excluir headers
                $totalRecords += count($dataRows);
            }
            
            Log::info("📈 [PROCESSOR] Total de registros calculados: {$totalRecords}");
            
            // PASO 2: Crear los jobs con el total correcto
            foreach ($sheets as $sheetIndex => $sheet) {
                $headers = $this->normalizeHeaders($sheet[0]);
                $dataRows = array_slice($sheet, 1);
                $chunks = array_chunk($dataRows, $this->chunkSize);
                
                Log::info("📋 [PROCESSOR] Hoja {$sheetIndex}: " . count($dataRows) . " registros, " . count($chunks) . " chunks");
                
                foreach ($chunks as $chunkIndex => $chunk) {
                    $batchJobs[] = new ProcessNoteChunkJob(
                        $companyId,
                        $typeEducationId,
                        $teacherId,
                        $headers,
                        $chunk,
                        $sheetIndex,
                        $chunkIndex,
                        $totalRecords // AHORA este valor es consistente para todos los jobs
                    );
                }
            }

            Log::info("🚀 [PROCESSOR] Creando batch con " . count($batchJobs) . " jobs");

            // USAR COLA ESPECÍFICA PARA IMPORTACIONES
            $batch = Bus::batch($batchJobs)
                ->name('ProcessEducationNotes_' . now()->format('Y-m-d_H-i-s'))
                // ->onQueue('imports') // Cola específica
                ->allowFailures()
                ->dispatch();

            Log::info("✅ [PROCESSOR] Batch creado exitosamente: {$batch->id}");

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'total_sheets' => count($sheets),
                'total_chunks' => count($batchJobs),
                'total_records' => $totalRecords
            ];
        } catch (\Exception $e) {
            Log::error("💥 [PROCESSOR] Error procesando archivo: " . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map('strtoupper', array_map('trim', $headers));
    }
}
