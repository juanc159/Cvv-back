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
        protected int $totalRecords
    ) {}

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        // Log::info("[Batch: {$this->batch()->id}] Iniciando chunk {$this->chunkIndex} de la hoja {$this->sheetIndex} - Total records: {$this->totalRecords}");

        try {
            DB::transaction(function () {
                $typeEducation = TypeEducation::with(['grades.subjects'])
                    ->findOrFail($this->typeEducationId);
                $subjects = $this->getSubjects($typeEducation);
                $totalRows = count($this->rows);

                foreach ($this->rows as $index => $row) {
                    $formattedRow = array_combine($this->headers, $row);
                    $formattedRow = array_map('trim', $formattedRow);

                    // INCREMENTAR CONTADOR GLOBAL (siempre, incluso para registros vacíos)
                    $cacheKey = "batch_processed_{$this->batch()->id}";
                    $processedRecords = Cache::increment($cacheKey, 1);

                    // CALCULAR PROGRESO POR CHUNK
                    $chunkProgress = intval((($index + 1) / $totalRows) * 100);
                    
                    // CALCULAR PROGRESO GENERAL (basado en registros procesados vs total)
                    $generalProgress = $this->totalRecords > 0 ? intval(($processedRecords / $this->totalRecords) * 100) : 0;
                    
                    // Asegurar que no pase del 100%
                    $generalProgress = min($generalProgress, 100);

                    // Log para debug del progreso general
                    // Log::debug("Progreso general calculado", [
                    //     'batch_id' => $this->batch()->id,
                    //     'sheet' => $this->sheetIndex,
                    //     'chunk' => $this->chunkIndex,
                    //     'processed_records' => $processedRecords,
                    //     'total_records' => $this->totalRecords,
                    //     'general_progress' => $generalProgress,
                    //     'student' => $formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'] ?? 'Registro vacío'
                    // ]);

                    if (empty($formattedRow['CÉDULA'])) {
                        event(new ImportProgressEvent(
                            $this->batch()->id,
                            $chunkProgress,
                            'Registro vacío - saltando',
                            'Procesando registros',
                            [
                                'sheet' => $this->sheetIndex + 1,
                                'chunk' => $this->chunkIndex + 1,
                                'current_row' => $index + 1,
                                'total_rows' => $totalRows,
                                'total_records' => $this->totalRecords,
                                'processed_records' => $processedRecords,
                                'general_progress' => $generalProgress
                            ]
                        ));
                        continue;
                    }

                    event(new ImportProgressEvent(
                        $this->batch()->id,
                        $chunkProgress,
                        $formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'],
                        'Procesando notas',
                        [
                            'sheet' => $this->sheetIndex + 1,
                            'chunk' => $this->chunkIndex + 1,
                            'current_row' => $index + 1,
                            'total_rows' => $totalRows,
                            'total_records' => $this->totalRecords,
                            'processed_records' => $processedRecords,
                            'general_progress' => $generalProgress
                        ]
                    ));

                    $student = $this->processStudent($formattedRow, $typeEducation);
                    $this->processNotes($student, $formattedRow, $subjects, $typeEducation->cantNotes);
                }
            });

            $this->checkIfCompleted();
            // Log::info("[Batch: {$this->batch()->id}] Chunk {$this->chunkIndex} completado");

        } catch (\Exception $e) {
            // Log::error("[Batch: {$this->batch()->id}] Error en chunk {$this->chunkIndex}: " . $e->getMessage());
            // Log::error("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    protected function checkIfCompleted(): void
    {
        $batch = $this->batch();
        
        if ($batch->pendingJobs <= 1) {
            // Obtener el contador final
            $cacheKey = "batch_processed_{$batch->id}";
            $finalProcessedRecords = Cache::get($cacheKey, $this->totalRecords);
            
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
                    'general_progress' => 100
                ]
            ));

            Cache::forget($cacheKey);
            // Log::info("Proceso COMPLETADO - Batch: {$batch->id} | Total records: {$this->totalRecords} | Processed: {$finalProcessedRecords}");
        }
    }

    protected function getSubjects(TypeEducation $typeEducation)
    {
        if ($this->teacherId && $this->teacherId != 'null') { // Tu validación
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
