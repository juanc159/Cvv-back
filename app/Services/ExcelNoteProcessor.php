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
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;

class ExcelNoteProcessor
{
    protected $chunkSize = 20;

    public function processFile(
        string $filePath,
        string $companyId,
        string $user_id,
        string $typeEducationId,
        ?string $teacherId = null
    ): array {
        try {
            $fileSize = filesize($filePath);
            $fileName = basename($filePath);
            $processingStartTime = now()->toDateTimeString();

            $sheets = Excel::toArray([], $filePath);
            $batchJobs = [];

            $totalRecords = 0;
            $totalSheets = count($sheets);
            $sheetRecordCounts = [];

            foreach ($sheets as $sheetIndex => $sheet) {
                $dataRows = array_slice($sheet, 1);
                $recordCount = count($dataRows);
                $sheetRecordCounts[$sheetIndex] = $recordCount;
                $totalRecords += $recordCount;
            }

            Log::info("📈 [PROCESSOR] Total de registros calculados: {$totalRecords}");
            Log::info("📄 [PROCESSOR] Total de hojas: {$totalSheets}");

            $initialMetadata = [
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'status' => 'active',
                'total_sheets' => $totalSheets,
                'total_rows' => $totalRecords,
                'started_at' => $processingStartTime, // Add started_at for progress calculation
                'current_sheet' => 1,
            ];

            

            $recordsBeforeSheet = 0;

            foreach ($sheets as $sheetIndex => $sheet) {
                $headers = $this->normalizeHeaders($sheet[0]);
                $dataRows = array_slice($sheet, 1);
                $chunks = array_chunk($dataRows, $this->chunkSize);

                Log::info("📋 [PROCESSOR] Hoja " . ($sheetIndex + 1) . "/{$totalSheets}: " . count($dataRows) . " registros, " . count($chunks) . " chunks");

                foreach ($chunks as $chunkIndex => $chunk) {
                    $batchJobs[] = new ProcessNoteChunkJob(
                        $companyId,
                        $typeEducationId,
                        $teacherId,
                        $headers,
                        $chunk,
                        $sheetIndex,
                        $chunkIndex,
                        $totalRecords,
                        $totalSheets,
                        $recordsBeforeSheet,
                        $initialMetadata
                    );
                }

                $recordsBeforeSheet += count($dataRows);
            }

            Log::info("🚀 [PROCESSOR] Creando batch con " . count($batchJobs) . " jobs");

            $batch = Bus::batch($batchJobs)
                ->name('ProcessEducationNotes_' . now()->format('Y-m-d_H-i-s'))
                ->allowFailures()
                ->dispatch();

            Redis::hmset("batch:{$batch->id}:metadata", $initialMetadata);
            Redis::set("batch_current_sheet_{$batch->id}", 1);

            Redis::set("batch_processed_{$batch->id}", 0);
            Redis::set("batch_errors_{$batch->id}", 0);
            Redis::set("batch_warnings_{$batch->id}", 0);

            // Iniciar registro en BD usando ProcessBatchService
            $processBatch = ProcessBatchService::initProcess(
               $batch->id,
                 $companyId,
                $user_id,
                $totalRecords,
                $initialMetadata
            );

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'total_sheets' => $totalSheets,
                'total_chunks' => count($batchJobs),
                'total_records' => $totalRecords,
                'file_size' => $fileSize,
                'processing_start_time' => $processingStartTime,
                'sheet_record_counts' => $sheetRecordCounts,
            ];
        } catch (\Exception $e) {
            Log::error("💥 [PROCESSOR] Error procesando archivo: " . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($batch)) {
                Redis::del("batch_processed_{$batch->id}");
                Redis::del("batch_errors_{$batch->id}");
                Redis::del("batch_warnings_{$batch->id}");
                Redis::del("batch_current_sheet_{$batch->id}");
                Redis::del("batch:{$batch->id}:metadata");
            }

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
