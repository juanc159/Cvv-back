<?php

namespace App\Jobs;

use App\Events\ImportProgressEvent;
use App\Helpers\ErrorCollector;
use App\Services\ConsolidatedImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessConsolidatedImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $data;
    protected $batchId;
    public $timeout = 7200;

    // Propiedades para permisos del docente
    protected $teacherPermissions = null;
    protected $subjectCodeToIdMap = [];

    public function __construct($filePath, array $data, string $batchId)
    {
        $this->filePath = $filePath;
        $this->data = $data;
        $this->batchId = $batchId;

        Log::info("ProcessConsolidatedImportJob CONSTRUIDO - File: {$this->filePath}, Batch: {$this->batchId}");

        // Cargar permisos del docente si existe teacher_id
        if (!empty($data['teacher_id'])) {
            $this->loadTeacherPermissions($data['teacher_id']);
        }
    }

    /**
     * Cargar los permisos del docente y estructurarlos
     */
    protected function loadTeacherPermissions($teacherId)
    {
        $teacher = \App\Models\Teacher::with(['complementaries.grade', 'complementaries.section'])
            ->find($teacherId);

        if (!$teacher) {
            Log::warning("Docente no encontrado: {$teacherId}");
            return;
        }

        // Estructura que espera el Servicio
        $this->teacherPermissions = [
            'mapa_permisos' => [],     // ['GRADO' => ['SECCION' => [ids...]]]
            'materias_permitidas' => [], // Lista plana de IDs para validaciones rápidas
            'codigos_permitidos' => []
        ];

        foreach ($teacher->complementaries as $comp) {
            if ($comp->grade && $comp->section) {
                $gName = strtoupper(trim($comp->grade->name));
                $sName = strtoupper(trim($comp->section->name));

                // IDs de materias asignadas en este salón
                $subjectIds = array_map('trim', explode(',', $comp->subject_ids));

                // 1. Mapa de permisos por grado/sección
                if (!isset($this->teacherPermissions['mapa_permisos'][$gName][$sName])) {
                    $this->teacherPermissions['mapa_permisos'][$gName][$sName] = [];
                }

                $this->teacherPermissions['mapa_permisos'][$gName][$sName] = array_merge(
                    $this->teacherPermissions['mapa_permisos'][$gName][$sName],
                    $subjectIds
                );

                // 2. Lista plana acumulada
                $this->teacherPermissions['materias_permitidas'] = array_merge(
                    $this->teacherPermissions['materias_permitidas'],
                    $subjectIds
                );
            }
        }

        // Limpiar duplicados
        $this->teacherPermissions['materias_permitidas'] = array_unique($this->teacherPermissions['materias_permitidas']);

        // Log::info("Permisos de docente cargados para ID {$teacherId}");
        // Log::info("Mapa de permisos: " . json_encode($this->teacherPermissions['mapa_permisos']));
    }

    public function handle()
    {
         // Activar Log de Consultas para monitoreo
        \Illuminate\Support\Facades\DB::enableQueryLog();
        
        // Aumentar memoria para el proceso
        ini_set('memory_limit', '1024M');

        // Validar existencia física del archivo
        if (!Storage::disk('public')->exists($this->filePath)) {
            Log::error("Archivo no encontrado: {$this->filePath}");
            $this->updateProgress(0, 0, "Error: Archivo no encontrado.", 'error');

            ErrorCollector::addError(
                $this->batchId,
                0,
                null,
                "Archivo no encontrado: {$this->filePath}",
                'SYSTEM_ERROR',
                null,
                null
            );

            ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');
            return;
        }

        $fullPath = Storage::disk('public')->path($this->filePath);
        $redisKey = "batch:{$this->batchId}:metadata";

        try {
            // Log::info("Iniciando Job. Batch: {$this->batchId}");

            // Limpiar errores previos de este batch si existieran
            ErrorCollector::clear($this->batchId);

            // 1. Preparar Lector Excel (Solo lectura de datos)
            $inputFileType = IOFactory::identify($fullPath);
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);

            // 2. Fase de Preparación: Contar Hojas y Filas Reales
            $this->updateProgress(0, 100, "Calculando total de registros...", 'active');

            $sheetNames = $reader->listWorksheetNames($fullPath);
            $totalSheets = count($sheetNames);
            $totalRowsGlobal = 0;

            // Recorrido rápido para contar filas sin cargar datos en memoria
            foreach ($sheetNames as $sheetName) {
                $reader->setLoadSheetsOnly($sheetName);
                $infoSheet = $reader->load($fullPath)->getActiveSheet();
                $rowsCount = $infoSheet->getHighestRow();
                $totalRowsGlobal += max(0, $rowsCount - 1); // Restar cabecera

                $infoSheet->disconnectCells();
                unset($infoSheet);
            }

            // Log::info("Metadatos calculados: {$totalSheets} hojas, {$totalRowsGlobal} filas.");

            // 3. Actualizar Metadata en Redis para el Frontend
            Redis::hmset($redisKey, [
                'total_sheets' => $totalSheets,
                'total_rows'   => $totalRowsGlobal,
                'current_sheet' => 1,
                'file_size'    => filesize($fullPath),
            ]);

            // 4. Instanciar y Configurar Servicio
            $importService = new ConsolidatedImportService($this->data["company_id"]);
            $importService->setBatchId($this->batchId);
            
            // Pasar permisos del docente al servicio (si existen)
            if ($this->teacherPermissions) {
                $importService->setTeacherPermissions($this->teacherPermissions);
            }

            $globalProcessedCount = 0;
            $errorCount = 0;

            // 5. Procesamiento Hoja por Hoja
            foreach ($sheetNames as $index => $sheetName) {

                // Actualizar hoja actual en Redis
                Redis::hset($redisKey, 'current_sheet', $index + 1);

                // Cargar SOLO esta hoja en memoria
                $reader->setLoadSheetsOnly($sheetName);
                $spreadsheet = $reader->load($fullPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray(); // Array crudo

                // Liberar objeto spreadsheet inmediatamente
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                // Si la hoja está vacía o solo tiene headers
                if (count($rows) < 2) {
                    unset($rows);
                    continue;
                }

                // Normalizar Encabezados
                $headers = array_map(function ($h) {
                    return trim(strtoupper((string)$h));
                }, $rows[0]);
                
                $dataRows = array_slice($rows, 1);

                // Recorrer Filas de la Hoja
                foreach ($dataRows as $rowIndex => $row) {
                    $rowNumber = $rowIndex + 2; // +1 por header, +1 por índice 0

                    // Normalizar Fila
                    $row = array_pad($row, count($headers), null);
                    $rowData = array_combine($headers, array_slice($row, 0, count($headers)));

                    // Configurar fila actual en servicio para logs de error correctos
                    $importService->setCurrentRowNumber($rowNumber);

                    // Validación mínima
                    if (empty($rowData['CÉDULA'])) {
                        continue;
                    }

                    // --- PROCESAMIENTO PRINCIPAL ---
                    $importService->processRow($rowData, $this->data, $headers);
                    // -------------------------------

                    $globalProcessedCount++;

                    // Actualizar contadores de error en BD cada 50 filas
                    if ($globalProcessedCount % 50 === 0) {
                        $errorCount = ErrorCollector::countErrors($this->batchId);
                    }

                    // Actualizar Progreso al Frontend cada 10 filas
                    if ($globalProcessedCount % 10 === 0) {
                        $this->updateProgress(
                            $globalProcessedCount,
                            $totalRowsGlobal,
                            "Procesando hoja " . ($index + 1) . "/{$totalSheets}",
                            'active',
                            $errorCount
                        );
                    }
                }

                // Limpieza agresiva de memoria al terminar la hoja
                unset($rows);
                unset($dataRows);
                gc_collect_cycles();
            }

            // 6. Finalización
            $errorCount = ErrorCollector::countErrors($this->batchId);
            Log::info("Procesamiento completado. Total errores: {$errorCount}");

            $status = $errorCount > 0 ? 'completed_with_errors' : 'completed';
            
            // Guardar estado final en BD de errores
            ErrorCollector::saveErrorsToDatabase($this->batchId, $status);

            $message = $errorCount > 0
                ? "Carga completada con {$errorCount} errores"
                : "Carga completada exitosamente";

            // Evento Final
            $this->updateProgress(
                $totalRowsGlobal,
                $totalRowsGlobal,
                $message,
                $status,
                $errorCount
            );

            // Eliminar archivo temporal
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

            // 7. Reporte de Consultas (Optimización)
            $queries = \Illuminate\Support\Facades\DB::getQueryLog();
            
            $inserts = 0;
            $updates = 0;
            $selects = 0;

            foreach ($queries as $q) {
                $sql = strtolower($q['query']);
                if (strpos($sql, 'insert') !== false) $inserts++;
                if (strpos($sql, 'update') !== false) $updates++;
                if (strpos($sql, 'select') !== false) $selects++;
            }

            Log::info("MÉTRICAS FINALIZADAS - Total Queries: " . count($queries));
            Log::info("INSERTS: $inserts, UPDATES: $updates, SELECTS: $selects");

        } catch (\Exception $e) {
            Log::error("Error Crítico en Job: " . $e->getMessage());

            // Registrar error sistémico
            ErrorCollector::addError(
                $this->batchId,
                0,
                null,
                "Error crítico del sistema: " . $e->getMessage(),
                'SYSTEM_ERROR',
                null,
                null
            );

            ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');

            $this->updateProgress(0, 0, "Error: " . $e->getMessage(), 'error');
        } 
    }



    protected function updateProgress($processed, $total, $action, $status = 'active', $errorCount = 0)
    {
        // Log::info("entreo al updateProgress - Processed: {$processed}/{$total}, Action: {$action}, Status: {$status}, Errors: {$errorCount}");
        
        ImportProgressEvent::dispatch(
            $this->batchId,
            (string)$processed,
            $action,
            (string)$errorCount,
            $status,
            (string)$total
        );
    }
}
