<?php

namespace App\Services;

use App\Jobs\ProcessExcelDataJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExcelDataProcessor
{
    // ... (métodos anteriores se mantienen igual)

    public function processInBatches(
        string $filePath,
        int $companyId,
        int $typeEducationId,
        ?int $teacherId = null,
        int $chunkSize = 100
    ): array {
        $sheets = Excel::toArray([], $filePath);
        $batchJobs = [];

        foreach ($sheets as $sheetIndex => $sheet) {
            $headers = array_map('strtoupper', $sheet[0]);
            $dataRows = array_slice($sheet, 1);
            
            // Dividir en chunks
            $chunks = array_chunk($dataRows, $chunkSize);
            
            foreach ($chunks as $chunk) {
                $batchJobs[] = new ProcessExcelDataJob(
                    $filePath,
                    $companyId,
                    $typeEducationId,
                    $teacherId,
                    ['headers' => $headers, 'rows' => $chunk],
                    $sheetIndex
                );
            }
        }

        $batch = Bus::batch($batchJobs)
            ->name('ProcessExcelData')
            ->allowFailures()
            ->dispatch();

        return [
            'batch_id' => $batch->id,
            'total_sheets' => count($sheets),
            'total_chunks' => count($batchJobs)
        ];
    }

    public function processChunk(
        string $filePath,
        int $companyId,
        int $typeEducationId,
        ?int $teacherId,
        array $chunkData,
        int $sheetIndex
    ): void {
        DB::transaction(function () use ($companyId, $typeEducationId, $teacherId, $chunkData) {
            $typeEducation = $this->typeEducationRepository->find($typeEducationId, ['grades.subjects']);
            $subjects = $this->getSubjects($teacherId, $typeEducation);

            foreach ($chunkData['rows'] as $row) {
                $formattedRow = array_combine($chunkData['headers'], $row);
                $formattedRow = array_map('trim', $formattedRow);

                if (empty($formattedRow['CÉDULA'])) continue;

                $student = $this->processStudent($formattedRow, $companyId, $typeEducationId);
                $this->processStudentAttributes($student, $formattedRow);
                $this->processNotes($student, $formattedRow, $subjects, $typeEducation->cantNotes);
            }
        });
    }
}