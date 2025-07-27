<?php

namespace App\Jobs;

use App\Events\ImportCompletedEvent;
use App\Events\ImportProgressEvent;
use App\Models\{
    Student,
    Note,
    Teacher,
    TypeEducation,
    Grade,
    Section,
    Subject
};
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessNoteChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $companyId,
        protected string $typeEducationId,
        protected ?string $teacherId,
        protected array $headers,
        protected array $rows,
        protected int $sheetIndex,
        protected int $chunkIndex,
        protected int $totalRecords,
        protected array $initialMetadata = [] // ✅ NUEVO PARÁMETRO
    ) {}

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            // ✅ OBTENER METADATA INICIAL
            $batchMetadata = Cache::get("batch_metadata_{$this->batch()->id}", $this->initialMetadata);
            
            // ✅ CALCULAR MEMORIA Y CPU (aproximado)
            $memoryUsage = memory_get_usage(true);
            $startTime = microtime(true);

            DB::transaction(function () use ($batchMetadata, $memoryUsage, $startTime) {
                $typeEducation = TypeEducation::with(['grades.subjects'])
                    ->findOrFail($this->typeEducationId);

                $subjects = $this->getSubjects($typeEducation);
                $totalRows = count($this->rows);
                $errorsCount = 0;
                $warningsCount = 0;

                foreach ($this->rows as $index => $row) {
                    $formattedRow = array_combine($this->headers, $row);
                    $formattedRow = array_map('trim', $formattedRow);

                    // INCREMENTAR CONTADOR GLOBAL
                    $cacheKey = "batch_processed_{$this->batch()->id}";
                    $processedRecords = Cache::increment($cacheKey, 1);

                    // CALCULAR PROGRESO POR CHUNK
                    $chunkProgress = intval((($index + 1) / $totalRows) * 100);
                    
                    // CALCULAR PROGRESO GENERAL
                    $generalProgress = $this->totalRecords > 0 ? intval(($processedRecords / $this->totalRecords) * 100) : 0;
                    $generalProgress = min($generalProgress, 100);

                    // ✅ DETECTAR ERRORES Y WARNINGS
                    $hasError = false;
                    $hasWarning = false;

                    if (empty($formattedRow['CÉDULA'])) {
                        $hasWarning = true;
                        $warningsCount++;
                        
                        event(new ImportProgressEvent(
                            $this->batch()->id,
                            $chunkProgress,
                            'Registro vacío - saltando',
                            'Procesando registros',
                            $this->buildMetadata($batchMetadata, $index, $totalRows, $processedRecords, $generalProgress, $errorsCount, $warningsCount, $memoryUsage)
                        ));
                        continue;
                    }

                    // ✅ VALIDACIONES ADICIONALES
                    if (empty($formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'])) {
                        $hasWarning = true;
                        $warningsCount++;
                    }

                    try {
                        event(new ImportProgressEvent(
                            $this->batch()->id,
                            $chunkProgress,
                            $formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'],
                            'Procesando notas',
                            $this->buildMetadata($batchMetadata, $index, $totalRows, $processedRecords, $generalProgress, $errorsCount, $warningsCount, $memoryUsage)
                        ));

                        $student = $this->processStudent($formattedRow, $typeEducation);
                        $this->processNotes($student, $formattedRow, $subjects, $typeEducation->cantNotes);

                    } catch (\Exception $e) {
                        $errorsCount++;
                        $hasError = true;
                        Log::warning("Error procesando estudiante: " . $e->getMessage(), [
                            'batch_id' => $this->batch()->id,
                            'student' => $formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'] ?? 'Desconocido',
                            'cedula' => $formattedRow['CÉDULA'] ?? 'Sin cédula'
                        ]);
                    }
                }

                // ✅ ACTUALIZAR CONTADORES DE ERRORES EN CACHE
                Cache::put("batch_errors_{$this->batch()->id}", $errorsCount, now()->addHours(2));
                Cache::put("batch_warnings_{$this->batch()->id}", $warningsCount, now()->addHours(2));
            });

            $this->checkIfCompleted($batchMetadata);

        } catch (\Exception $e) {
            Log::error("[Batch: {$this->batch()->id}] Error en chunk {$this->chunkIndex}: " . $e->getMessage());
            
            // ✅ INCREMENTAR CONTADOR DE ERRORES
            $errorsKey = "batch_errors_{$this->batch()->id}";
            Cache::increment($errorsKey, 1);
            
            throw $e;
        }
    }

    // ✅ FUNCIÓN PARA CONSTRUIR METADATA COMPLETA
    protected function buildMetadata(
        array $batchMetadata, 
        int $currentIndex, 
        int $totalRows, 
        int $processedRecords, 
        int $generalProgress,
        int $errorsCount,
        int $warningsCount,
        int $memoryUsage
    ): array {
        return [
            'sheet' => $this->sheetIndex + 1,
            'chunk' => $this->chunkIndex + 1,
            'current_row' => $currentIndex + 1,
            'total_rows' => $totalRows,
            'total_records' => $this->totalRecords,
            'processed_records' => $processedRecords,
            'general_progress' => $generalProgress,
            // ✅ DATOS ADICIONALES
            'current_sheet' => $this->sheetIndex + 1,
            'total_sheets' => $batchMetadata['total_sheets'] ?? 1,
            'errors_count' => $errorsCount + (Cache::get("batch_errors_{$this->batch()->id}", 0)),
            'warnings_count' => $warningsCount + (Cache::get("batch_warnings_{$this->batch()->id}", 0)),
            'file_size' => $batchMetadata['file_size'] ?? 0,
            'processing_start_time' => $batchMetadata['processing_start_time'] ?? null,
            'last_activity' => now()->toDateTimeString(),
            'memory_usage' => $memoryUsage,
            'cpu_usage' => 0, // Placeholder - difícil de calcular en PHP
            'connection_status' => 'connected',
        ];
    }

    protected function checkIfCompleted(array $batchMetadata): void
    {
        $batch = $this->batch();
        
        if ($batch->pendingJobs <= 1) {
            // Obtener contadores finales
            $cacheKey = "batch_processed_{$batch->id}";
            $finalProcessedRecords = Cache::get($cacheKey, $this->totalRecords);
            $finalErrors = Cache::get("batch_errors_{$batch->id}", 0);
            $finalWarnings = Cache::get("batch_warnings_{$batch->id}", 0);
            
            event(new ImportProgressEvent(
                $batch->id,
                100,
                'Proceso completado',
                'Finalizando importación',
                [
                    'sheet' => 0,
                    'chunk' => 0,
                    'current_row' => 0,
                    'total_rows' => 0,
                    'total_records' => $this->totalRecords,
                    'processed_records' => $finalProcessedRecords,
                    'general_progress' => 100,
                    // ✅ DATOS FINALES
                    'current_sheet' => $batchMetadata['total_sheets'] ?? 1,
                    'total_sheets' => $batchMetadata['total_sheets'] ?? 1,
                    'errors_count' => $finalErrors,
                    'warnings_count' => $finalWarnings,
                    'file_size' => $batchMetadata['file_size'] ?? 0,
                    'processing_start_time' => $batchMetadata['processing_start_time'] ?? null,
                    'last_activity' => now()->toDateTimeString(),
                    'memory_usage' => memory_get_usage(true),
                    'cpu_usage' => 0,
                    'connection_status' => 'connected',
                ]
            ));

            // Limpiar cache
            Cache::forget($cacheKey);
            Cache::forget("batch_errors_{$batch->id}");
            Cache::forget("batch_warnings_{$batch->id}");
            Cache::forget("batch_metadata_{$batch->id}");
            
            Log::info("Proceso COMPLETADO - Batch: {$batch->id} | Total: {$this->totalRecords} | Procesados: {$finalProcessedRecords} | Errores: {$finalErrors} | Warnings: {$finalWarnings}");
        }
    }

    protected function getSubjects(TypeEducation $typeEducation)
    {
        if ($this->teacherId && $this->teacherId != 'null') {
            return Teacher::with(['complementaries.subjects'])
                ->findOrFail($this->teacherId)
                ->complementaries
                ->flatMap
                ->subjects;
        }

        return $typeEducation->grades->flatMap->subjects;
    }

    protected function processStudent(array $row, TypeEducation $typeEducation): Student
    {
        return Student::updateOrCreate(
            ['identity_document' => $row['CÉDULA']],
            [
                'pdf' => isset($row['PDF']) ? ($row['PDF'] == 1) : null,
                'solvencyCertificate' => isset($row['SOLVENTE']) ? ($row['SOLVENTE'] == 1) : null
            ]
        );
    }

    protected function processNotes(
        Student $student,
        array $row,
        $subjects,
        int $cantNotes
    ): void {
        foreach ($subjects as $subject) {
            $notesData = [];
            for ($i = 1; $i <= $cantNotes; $i++) {
                $columnName = $subject->code . $i;
                if (isset($row[$columnName]) && $row[$columnName] !== '') {
                    $notesData[$i] = trim($row[$columnName]);
                }
            }

            if (!empty($notesData)) {
                Note::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subject->id
                    ],
                    ['json' => json_encode($notesData)]
                );
            }
        }
    }
}
