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
    ProcessBatch,
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
use Illuminate\Support\Facades\Redis;

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
        protected int $totalSheets,
        protected int $recordsBeforeThisSheet,
        protected array $initialMetadata = []
    ) {}

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            $batchId = $this->batch()->id;
            $batchMetadata = Redis::hgetall("batch:{$batchId}:metadata") ?: $this->initialMetadata;

            DB::transaction(function () use ($batchId) {
                $typeEducation = TypeEducation::with(['grades.subjects'])
                    ->findOrFail($this->typeEducationId);

                $subjects = $this->getSubjects($typeEducation);

                // Log::info("subjects=> ", [$subjects]);

                $errorsCount = 0;
                $warningsCount = 0;

                Redis::hset("batch:{$batchId}:metadata", 'current_sheet', $this->sheetIndex + 1);

                foreach ($this->rows as $index => $row) {
                    $formattedRow = array_combine($this->headers, $row);
                    $formattedRow = array_map('trim', $formattedRow);

                    $cacheKey = "batch_processed_{$batchId}";
                    $processedRecords = Redis::incr($cacheKey);

                    $currentRecordInSheet = $index + 1;

                    $currentErrors = Redis::get("batch_errors_{$batchId}") ?: 0;

                    if (empty($formattedRow['CÉDULA'])) {
                        $hasWarning = true;
                        $warningsCount++;

                        ImportProgressEvent::dispatch(
                            $batchId,
                            (string) $processedRecords,
                            "Procesando registros - Hoja " . ($this->sheetIndex + 1) . "/" . $this->totalSheets,
                            (string) $currentErrors,
                            'active',
                            'Registro vacío - saltando',
                        );

                        continue;
                    }

                    if (empty($formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'])) {
                        $hasWarning = true;
                        $warningsCount++;
                    }

                    try {
                        ImportProgressEvent::dispatch(
                            $batchId,
                            (string) $processedRecords,
                            "Procesando notas - Hoja " . ($this->sheetIndex + 1) . "/" . $this->totalSheets,
                            (string) $currentErrors,
                            'active',
                            $formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'],
                        );

                        $student = $this->processStudent($formattedRow);
                        $this->processNotes($student, $formattedRow, $subjects, $typeEducation->cantNotes);
                    } catch (\Exception $e) {
                        $errorsCount++;
                        $hasError = true;
                        Log::warning("Error procesando estudiante: " . $e->getMessage(), [
                            'batch_id' => $batchId,
                            'student' => $formattedRow['NOMBRES Y APELLIDOS ESTUDIANTE'] ?? 'Desconocido',
                            'cedula' => $formattedRow['CÉDULA'] ?? 'Sin cédula'
                        ]);
                    }
                }

                if ($errorsCount > 0) {
                    Redis::incrby("batch_errors_{$batchId}", $errorsCount);
                }
                if ($warningsCount > 0) {
                    Redis::incrby("batch_warnings_{$batchId}", $warningsCount);
                }
            });

            $this->checkIfCompleted($batchMetadata, $batchId);
        } catch (\Exception $e) {
            $errorsKey = "batch_errors_{$this->batch()->id}";
            Redis::incr($errorsKey);

            throw $e;
        }
    }

    protected function calculateProcessingSpeed(?string $startTime, int $processedRecords): int
    {
        if (!$startTime || $processedRecords === 0) {
            return 0;
        }

        try {
            $startTimestamp = strtotime($startTime);
            $currentTimestamp = time();
            $elapsedSeconds = $currentTimestamp - $startTimestamp;

            if ($elapsedSeconds <= 0) {
                return 0;
            }

            $speed = intval($processedRecords / $elapsedSeconds);

            return $speed;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function calculateEstimatedTime(?string $startTime, int $processedRecords, int $totalRecords, int $progress): int
    {
        if (!$startTime || $progress === 0 || $totalRecords === 0) {
            return 0;
        }

        try {
            $startTimestamp = strtotime($startTime);
            $currentTimestamp = time();
            $elapsedSeconds = $currentTimestamp - $startTimestamp;

            if ($elapsedSeconds <= 0) {
                return 0;
            }

            $remainingProgress = 100 - $progress;
            $estimatedTotalTime = ($elapsedSeconds * 100) / $progress;
            $estimatedRemainingByProgress = $estimatedTotalTime - $elapsedSeconds;

            $estimatedRemainingByRecords = 0;
            if ($processedRecords > 0) {
                $recordsPerSecond = $processedRecords / $elapsedSeconds;
                $remainingRecords = $totalRecords - $processedRecords;
                $estimatedRemainingByRecords = $remainingRecords / $recordsPerSecond;
            }

            $finalEstimate = $estimatedRemainingByProgress;
            if ($estimatedRemainingByRecords > 0) {
                $finalEstimate = ($estimatedRemainingByProgress + $estimatedRemainingByRecords) / 2;
            }

            $result = max(0, intval($finalEstimate));

            return $result;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function checkIfCompleted(array $batchMetadata, string $batchId): void
    {
        $batch = $this->batch();

        if ($batch->pendingJobs <= 1) {
            $cacheKey = "batch_processed_{$batchId}";
            $finalProcessedRecords = Redis::get($cacheKey) ?: $this->totalRecords;
            $finalErrors = Redis::get("batch_errors_{$batchId}") ?: 0;

            ImportProgressEvent::dispatch(
                $batchId,
                (string) $finalProcessedRecords,
                'Proceso completado',
                (string) $finalErrors,
                'completed',
                'Finalizando importación',
            );

            // Actualizar el estado y metadata en la tabla process_batches
            $processBatch = ProcessBatch::where('batch_id', $batchId)->first();
            if ($processBatch) {
                $metadata = json_decode($processBatch->metadata, true);
                $metadata['completed_at'] = now()->toDateTimeString();
                $processBatch->update([
                    'status' => 'completed',
                    'error_count' => (int) $finalErrors,
                    'metadata' => json_encode($metadata),
                ]);
            }

            Redis::del($cacheKey);
            Redis::del("batch_errors_{$batchId}");
            Redis::del("batch_warnings_{$batchId}");
            Redis::del("batch:{$batchId}:metadata");
        }
    }

    protected function getSubjects(TypeEducation $typeEducation)
    {
        if ($this->teacherId && $this->teacherId != 'null') {
            return Teacher::with(['complementaries'])
                ->findOrFail($this->teacherId)
                ->complementaries
                ->flatMap
                ->subjects;
        }

        return $typeEducation->grades->flatMap->subjects;
    }

    protected function processStudent(array $row): Student
    {
                Log::info("ACTULIZA ALUMNO");

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
                Log::info("ACTULIZA NOTA");
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
