<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Note;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\Section;
use App\Helpers\ErrorCollector;
use Illuminate\Support\Facades\Log;

class ConsolidatedImportService
{
    // Memoria caché
    protected $gradesMap = [];
    protected $sectionsMap = [];
    protected $subjectsMap = [];

    // Contexto del Job
    protected $batchId;
    protected $currentRowNumber = 0;

    // PERMISOS DOCENTE (null = Admin)
    protected $teacherPermissions = null;

    // LISTA NEGRA: Columnas que ignoramos
    protected $nonNoteHeaders = [
        'NRO',
        'PDF',
        'SOLVENTE',
        'AÑO',
        'SECCIÓN',
        'CÉDULA',
        'NOMBRES Y APELLIDOS ESTUDIANTE'
    ];

    // Constructor: Cargar mapas de referencia para validación y procesamiento
    public function __construct($company_id)
    {
        $this->gradesMap = Grade::where("company_id", $company_id)->pluck('id', 'name')->mapWithKeys(fn($item, $key) => [strtoupper(trim($key)) => $item])->toArray();
        $this->sectionsMap = Section::pluck('id', 'name')->mapWithKeys(fn($item, $key) => [strtoupper(trim($key)) => $item])->toArray();
        $this->subjectsMap = Subject::where("company_id", $company_id)->pluck('id', 'code')->mapWithKeys(fn($item, $key) => [strtoupper(trim($key)) => $item])->toArray();
    }

    public function setBatchId($batchId)
    {
        $this->batchId = $batchId;
    }
    public function setCurrentRowNumber($rowNumber)
    {
        $this->currentRowNumber = $rowNumber;
    }
    public function setTeacherPermissions(array $permissions)
    {
        $this->teacherPermissions = $permissions;
    }

    public function processRow(array $row, array $jobData, array $headers)
    {


        $nombreGrado = strtoupper(trim($row['AÑO']));
        $nombreSeccion = strtoupper(trim($row['SECCIÓN']));

        // ---------------------------------------------------
        // FASE 0: VALIDACIÓN DE PERMISOS (NIVEL FILA)
        // ---------------------------------------------------
        $allowedSubjectIds = null;

        if ($this->teacherPermissions !== null) {
            if (!isset($this->teacherPermissions['mapa_permisos'][$nombreGrado][$nombreSeccion])) {
                return; // Sin permiso en Grado/Sección
            }
            $allowedSubjectIds = $this->teacherPermissions['mapa_permisos'][$nombreGrado][$nombreSeccion];
        }

        // ---------------------------------------------------
        // FASE 1: PROCESAR ESTUDIANTE
        // ---------------------------------------------------
        $cedula = trim($row['CÉDULA']);
        $student = null;


        // === CAMINO A: ES DOCENTE (Lectura) ===
        if ($this->teacherPermissions !== null) {
            $student = Student::where('identity_document', $cedula)->first();
            if (!$student) {
                $this->addError('CÉDULA', "Estudiante no encontrado. Docente no puede crear.", 'DATA_ERROR', $cedula);
                return;
            }
        }
        // === CAMINO B: ES ADMIN (Escritura) ===
        else {




            $gradeId = $this->gradesMap[$nombreGrado] ?? null;
            $sectionId = $this->sectionsMap[$nombreSeccion] ?? null;

            if (!$gradeId || !$sectionId) {
                $this->addError('GRADO/SECCION', "Grado/Sección no existen", 'DATA_ERROR', "$nombreGrado - $nombreSeccion");
                return;
            }

            //por ahora solo actualizaremos los campos PDF y SOLVENTE, el resto de la info del estudiante no la tocaremos
            $studentData = [
                // 'full_name' => $row['NOMBRES Y APELLIDOS ESTUDIANTE'] ?? 'S/N',
                // 'grade_id' => $gradeId,
                // 'section_id' => $sectionId,
                // 'company_id' => $jobData['company_id'],
                // 'type_education_id' => $jobData['type_education_id'],
            ];
            $update = false;

            if (array_key_exists('PDF', $row)) {
                $update = true;
                $val = $row['PDF'];
                $studentData['pdf'] = ($val == 1 || $val === '1') ? 1 : 0;
            }
            if (array_key_exists('SOLVENTE', $row)) {
                $update = true;
                $val = $row['SOLVENTE'];
                $studentData['solvencyCertificate'] = ($val == 1 || $val === '1') ? 1 : 0;
            }

            if ($update) {
                try {
                    $student = Student::updateOrCreate(['identity_document' => $cedula], $studentData);
                } catch (\Exception $e) {
                    $this->addError('ESTUDIANTE', "Error DB: " . $e->getMessage(), 'DB_ERROR', $cedula);
                    return;
                }
            }
        }

        // ---------------------------------------------------
        // FASE 2: PROCESAR NOTAS
        // ---------------------------------------------------
        $notesBySubject = [];

        foreach ($headers as $header) {
            if (in_array($header, $this->nonNoteHeaders)) continue;

            if (preg_match('/^([A-Z]+)([0-9]+)$/', $header, $matches)) {
                $subjectCode = $matches[1];
                $lapso = $matches[2];

                if (isset($this->subjectsMap[$subjectCode])) {
                    $subjectId = $this->subjectsMap[$subjectCode];

                    // Filtro Docente: Verificar permiso en materia
                    if ($allowedSubjectIds !== null) {
                        if (!in_array($subjectId, $allowedSubjectIds)) {
                            continue; // Saltar
                        }
                    }

                    if (array_key_exists($header, $row)) {
                        $valorNota = $row[$header];

                        // --- AQUÍ USAMOS TU FUNCIÓN DE VALIDACIÓN ---
                        $validacion = $this->validateNoteValue($valorNota);

                        if ($validacion['valid']) {
                            // Solo guardamos si el valor no está vacío 
                            // (Si validateNoteValue retorna '' es porque venía vacío o null)
                            if ($validacion['value'] !== '') {
                                $notesBySubject[$subjectId][$lapso] = $validacion['value'];
                            }
                        } else {
                            // Si es inválido (ej: '25', 'XYZ', '%')
                            $this->addError($header, "Nota inválida (0-20 o Letra única)", 'VALIDATION_ERROR', $valorNota);
                            continue;
                        }
                    }
                }
            }
        }


        // ---------------------------------------------------
        // FASE 3: GUARDAR NOTAS
        // ---------------------------------------------------
        if (empty($notesBySubject)) return;

        try {
            $existingNotesCollection = Note::where('student_id', $student->id)
                ->get()
                ->keyBy('subject_id');

            foreach ($notesBySubject as $subjectId => $newNotes) {
                $noteRecord = $existingNotesCollection[$subjectId] ?? null;
                $jsonNotes = $noteRecord ? json_decode($noteRecord->json, true) : [];

                foreach ($newNotes as $lapso => $valor) {
                    $jsonNotes[$lapso] = $valor;
                }

                Note::updateOrCreate(
                    ['student_id' => $student->id, 'subject_id' => $subjectId],
                    ['json' => json_encode($jsonNotes)]
                );
            }
        } catch (\Exception $e) {
            $this->addError('NOTAS', "Error guardando notas: " . $e->getMessage(), 'DB_ERROR', 'N/A');
        }
    }

    /**
     * Valida si el valor de la nota es aceptable (0-20 o Letra A-Z)
     */
    protected function validateNoteValue($value)
    {
        // Si está vacío, es válido (pero retornamos vacío para no guardarlo si no queremos)
        if ($value === null || trim($value) === '') {
            return ['valid' => true, 'value' => ''];
        }

        $value = trim($value);

        // 1. Validar números del 0 al 20
        if (is_numeric($value)) {
            $num = floatval($value);
            if ($num >= 0 && $num <= 20) {
                return ['valid' => true, 'value' => $value];
            }
        }

        // 2. Validar letras simples (A-Z)
        // Convertimos a mayúscula si viene minúscula
        if (preg_match('/^[A-Za-z]$/', $value)) {
            return ['valid' => true, 'value' => strtoupper($value)];
        }

        // Si no pasa ninguna
        return ['valid' => false, 'value' => $value];
    }

    protected function addError($columnName, $errorMessage, $errorType, $errorValue)
    {
        if ($this->batchId) {
            ErrorCollector::addError($this->batchId, $this->currentRowNumber, $columnName, $errorMessage, $errorType, $errorValue, null);
        } else {
            Log::warning("ErrorCollector sin BatchID: $errorMessage");
        }
    }
}
