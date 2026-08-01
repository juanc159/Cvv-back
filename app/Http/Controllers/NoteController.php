<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedExport;
use App\Exports\ConsolidatedExportPercentage;
use App\Helpers\Constants;
use App\Models\BlockData;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Repositories\BlockDataRepository;
use App\Repositories\NoteRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TypeEducationRepository;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class NoteController extends Controller
{
    /**
     * Únicas columnas que la carga de archivos puede escribir, con el tipo que acepta cada una.
     *
     * Se valida por MIME real, no por la extensión del nombre: un archivo llamado ".jpg"
     * puede tener cualquier contenido, y termina guardado en el disco público.
     */
    private const ALLOWED_FILE_FIELDS = [
        'photo' => [
            'mimetypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'label' => 'una imagen (JPG, PNG, WEBP o GIF)',
        ],
        'boletin' => [
            'mimetypes' => ['application/pdf'],
            'label' => 'un archivo PDF',
        ],
    ];

    /** Tamaño máximo por archivo, en kilobytes. */
    private const MAX_FILE_KB = 10240;

    public function __construct(
        protected TypeEducationRepository $typeEducationRepository,
        protected StudentRepository $studentRepository,
        protected NoteRepository $noteRepository,
        protected UserRepository $userRepository,
        protected BlockDataRepository $blockDataRepository,
        protected TeacherComplementaryRepository $teacherComplementaryRepository,
        protected TeacherRepository $teacherRepository,
        protected SubjectRepository $subjectRepository,
    ) {}

    public function dataForm(Request $request)
    {
        Cache::put('Cache_Grade', Grade::get(), now()->addMinutes(60));
        Cache::put('Cache_Section', Section::get(), now()->addMinutes(60));

        $typeEducations = $this->typeEducationRepository->selectList(select: ['cantNotes']);


        $teachers = $this->teacherRepository->selectList([
            "company_id" => $request->input("company_id"),
        ], fieldTitle: "full_name");
        $blockData = BlockData::where('name', Constants::BLOCK_PAYROLL_UPLOAD)->first()->is_active;

        return response()->json([
            'typeEducations' => $typeEducations,
            'blockData' => $blockData,
            'prosecutionDocuments' => BlockData::isActive(Constants::ENABLE_PROSECUTION_DOCUMENTS),
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        // 1. Configuración para procesos pesados
        // set_time_limit(0);
        // ini_set('memory_limit', '512M');

        DB::beginTransaction();

        try {
            // Validación básica
            if (!$request->hasFile('archive')) {
                return response()->json(['code' => 400, 'message' => 'No se ha subido ningún archivo']);
            }

            $teacher_id = $request->teacher_id;
            $typeEducationId = $request->input('type_education_id');

            // ---------------------------------------------------------
            // FASE 1: PREPARACIÓN DE DATOS (OPTIMIZACIÓN)
            // ---------------------------------------------------------

            // A. Cargar Docente y sus Asignaciones
            $teacher = \App\Models\Teacher::with(['complementaries.grade', 'complementaries.section'])
                ->find($teacher_id);

            if (!$teacher) {
                throw new \Exception("Docente no encontrado");
            }

            // B. Obtener configuración de notas (ej: 3 lapsos)
            $typeEducation = $this->typeEducationRepository->find($typeEducationId);
            $cantNotes = $typeEducation ? $typeEducation->cantNotes : 3;

            // C. Construir "Mapa de Permisos" y lista de IDs de materias
            // Estructura: $mapaPermisos['Grado']['Seccion'] = [ID_Mat1, ID_Mat2...]
            $mapaPermisos = [];
            $todosIdsMaterias = [];


            foreach ($teacher->complementaries as $comp) {
                if ($comp->grade && $comp->section) {
                    $gName = trim($comp->grade->name);
                    $sName = trim($comp->section->name);

                    $ids = array_map('trim', explode(',', $comp->subject_ids));

                    // --- CAMBIO AQUÍ ---
                    // Verificar si ya existe el array, si no, crearlo
                    if (!isset($mapaPermisos[$gName][$sName])) {
                        $mapaPermisos[$gName][$sName] = [];
                    }

                    // Fusionar los nuevos IDs con los existentes
                    $mapaPermisos[$gName][$sName] = array_merge($mapaPermisos[$gName][$sName], $ids);
                    // -------------------

                    $todosIdsMaterias = array_merge($todosIdsMaterias, $ids);
                }
            }
 
            // D. Diccionario de Materias (ID => Código)
            // Esto evita buscar "SELECT code FROM subjects WHERE id=..." mil veces
            $todosIdsMaterias = array_unique($todosIdsMaterias);
            $diccionarioMaterias = \App\Models\Subject::whereIn('id', $todosIdsMaterias)
                ->pluck('code', 'id')
                ->toArray();
            // Resultado: [15 => 'fsn', 20 => 'mat']

            // E. Diccionario de Grados y Secciones (Nombre => ID)
            // Necesario para crear/buscar estudiantes sin consultar la BD en el bucle
            $mapaGrados = \App\Models\Grade::where('company_id', $teacher->company_id)->pluck('id', 'name')->toArray();
            $mapaSecciones = \App\Models\Section::pluck('id', 'name')->toArray();

            // ---------------------------------------------------------
            // FASE 2: PROCESAMIENTO DEL EXCEL
            // ---------------------------------------------------------

            $file = $request->file('archive');
            $import = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);

            foreach ($import as $sheetData) {
                if (empty($sheetData)) continue;

                // Extraer y limpiar encabezados
                $headerRow = array_shift($sheetData);
                $headerKeys = array_map('trim', $headerRow);

                foreach ($sheetData as $rowData) {
                    // Convertir fila a array asociativo
                    // array_slice asegura que no falle si la fila tiene más datos que el header
                    $row = array_combine($headerKeys, array_slice($rowData, 0, count($headerKeys)));

                    // 1. Validar datos mínimos
                    if (empty($row['CÉDULA']) || empty($row['AÑO']) || empty($row['SECCIÓN'])) {
                        continue;
                    }

                    $cedula = trim($row['CÉDULA']);
                    $nombreGrado = trim($row['AÑO']);
                    $nombreSeccion = trim($row['SECCIÓN']);

                    // 2. FILTRO DE SEGURIDAD MAESTRO
                    // Si el profesor no tiene asignada esta combinación Grado/Sección, ADIÓS.
                    if (!isset($mapaPermisos[$nombreGrado][$nombreSeccion])) {
                        continue;
                    }

                    // Recuperar materias permitidas para ESTA fila
                    $materiasPermitidasIds = $mapaPermisos[$nombreGrado][$nombreSeccion];

                    // 3. Buscar o Crear Estudiante
                    $student = $this->studentRepository->searchOne(['identity_document' => $cedula]);

                    if (!$student) {
                        // Validar que tengamos los IDs para crear
                        $gradeId = $mapaGrados[$nombreGrado] ?? null;
                        $sectionId = $mapaSecciones[$nombreSeccion] ?? null;

                        if ($gradeId && $sectionId) {
                            $studentData = [
                                'id' => $student ? $student->id : null,
                                // 'identity_document' => $cedula,
                                // 'full_name' => $row['NOMBRES Y APELLIDOS ESTUDIANTE'] ?? 'Estudiante Nuevo',
                                // 'grade_id' => $gradeId,
                                // 'section_id' => $sectionId,
                                // 'company_id' => $teacher->company_id,
                                // 'type_education_id' => $typeEducationId,
                            ];


                            // REGLA: Campos opcionales (PDF y Solvencia)
                            // Solo se agregan al array si la columna EXISTE en el Excel
                            if (array_key_exists('PDF', $row)) {
                                // Si la columna existe, tomamos el valor (1 o 0)
                                $val = trim($row['PDF']);
                                $studentData['pdf'] = ($val == 1 || $val === '1') ? 1 : 0;
                            }

                            if (array_key_exists('SOLVENTE', $row)) {
                                $val = trim($row['SOLVENTE']);
                                $studentData['solvencyCertificate'] = ($val == 1 || $val === '1') ? 1 : 0;
                            }


                            // Usamos tu repositorio existente
                            $student = $this->studentRepository->store($studentData);
                        } else {
                            // Si no encontramos el ID del grado/sección en BD, no podemos crear al alumno
                            continue;
                        }
                    }
 
                    // 4. GUARDAR NOTAS (Solo de materias permitidas)
                    foreach ($materiasPermitidasIds as $subjectId) {

                        // Obtener código (ej: 'fsn')
                        if (!isset($diccionarioMaterias[$subjectId])) continue;
                        $code = $diccionarioMaterias[$subjectId];

                        // Buscar nota existente para este alumno y materia
                        // Nota: Idealmente searchOne debería estar cacheado o optimizado, 
                        // pero al filtrar por materias permitidas reducimos el impacto.
                        $noteRecord = $this->noteRepository->searchOne([
                            'student_id' => $student->id,
                            'subject_id' => $subjectId
                        ]);

                        $jsonNotes = $noteRecord ? json_decode($noteRecord->json, true) : [];
                        $huboCambios = false;

                        // Recorrer lapsos (1, 2, 3...)
                        for ($i = 1; $i <= $cantNotes; $i++) {
                            $excelKey = $code . $i; // ej: fsn1

                            if (array_key_exists($excelKey, $row)) {
                                $val = trim($row[$excelKey]);

                                // Actualizamos solo si tiene valor. 
                                // Si quieres permitir borrar notas, quita el check de !== ''
                                if ($val !== '') {
                                    // Convertir a string o float según tu necesidad
                                    $jsonNotes[$i] = $val;
                                    $huboCambios = true;
                                }
                            }
                        }

                        // Guardar en BD si es necesario
                        if ($huboCambios) {
                            $saveParams = [
                                'id' => $noteRecord ? $noteRecord->id : null,
                                'student_id' => $student->id,
                                'subject_id' => $subjectId,
                                'json' => json_encode($jsonNotes)
                            ];
                            $this->noteRepository->store($saveParams);
                        }
                    } // Fin foreach materias
                } // Fin foreach filas
            } // Fin foreach hojas

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => 'Carga masiva procesada correctamente.'
            ]);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => 'Error en la carga: ' . $th->getMessage(),
                'line' => $th->getLine()
            ], 500);
        }
    }

    public function grade($value, $field)
    {
        $cache = collect(Cache::get('Cache_Grade'));
        $data = $cache->first(function ($item) use ($value, $field) {
            return strtoupper($item[$field]) === strtoupper($value);
        });

        return $data;
    }

    public function section($value, $field)
    {
        $cache = collect(Cache::get('Cache_Section'));
        $data = $cache->first(function ($item) use ($value, $field) {
            return strtoupper($item[$field]) === strtoupper($value);
        });

        return $data;
    }

    public function blockPayrollUpload(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = BlockData::where('name', Constants::BLOCK_PAYROLL_UPLOAD)->first();
            $model->is_active = $request->input('value');
            $model->save();

            ($model->is_active == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Carga de archivos ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Enciende o apaga las constancias/certificados de prosecución del portal del estudiante.
     *
     * Solo se usan a fin de año escolar. Con el interruptor apagado el alumno no ve la tarjeta
     * y el backend rechaza la descarga aunque le armen la URL a mano.
     */
    public function toggleProsecutionDocuments(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = BlockData::firstOrNew(['name' => Constants::ENABLE_PROSECUTION_DOCUMENTS]);
            $model->is_active = (bool) $request->input('value');
            $model->save();

            $msg = $model->is_active ? 'Activadas' : 'Desactivadas';

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => 'Constancias de prosecución ' . $msg . ' con éxito',
                'is_active' => $model->is_active,
            ]);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function downloadAllConsolidated(Request $request)
    {
          try {
            $companyId = $request->input('company_id');
            // Inicializamos la variable, pero puede cambiar si buscamos al profesor
            $typeEducationId = $request->input('type_education_id');
            $teacherId = $request->input('teacher_id');

            // LÓGICA DE AUTODETECCIÓN
            // Si no mandan el tipo de educación, pero SÍ mandan el profesor, lo buscamos.
            if (empty($typeEducationId) && !empty($teacherId)) {
                $teacher = \App\Models\Teacher::find($teacherId);
                if ($teacher) {
                    $typeEducationId = $teacher->type_education_id;
                } else {
                    return response()->json(['code' => 404, 'message' => 'Profesor no encontrado']);
                }
            }

            // Validación de seguridad: A estas alturas DEBE haber un type_education_id
            if (empty($typeEducationId)) {
                return response()->json(['code' => 422, 'message' => 'Falta el ID del tipo de educación']);
            }

            // --- AHORA SÍ, LA CONSULTA QUE YA TENÍAMOS ---

            // 1. Obtener Asignaciones
            $assignmentsQuery = \App\Models\TeacherComplementary::query()
                ->with([
                    'grade:id,name',
                    'section:id,name',
                    'teacher' => function ($q) use ($companyId, $typeEducationId) {
                        $q->select('id', 'company_id', 'type_education_id')
                            ->where('company_id', $companyId)
                            ->where('type_education_id', $typeEducationId);
                    }
                ])
                ->whereHas('teacher', function ($q) use ($companyId, $typeEducationId) {
                    $q->where('company_id', $companyId)
                        ->where('type_education_id', $typeEducationId);
                });

            // Aplicar filtro si tenemos el ID específico
            if (!empty($teacherId)) {
                $assignmentsQuery->where('teacher_id', $teacherId);
            }

            $assignments = $assignmentsQuery->get();


            // 2. Optimización: Obtener solo las materias de esta empresa Y de este tipo de educación
            $allSubjects = \App\Models\Subject::query()
                ->where('company_id', $companyId)
                ->where('type_education_id', $typeEducationId) // <--- Filtro clave que faltaba
                ->where('is_active', true) // <--- Buena práctica: solo materias activas
                ->pluck('code', 'id'); // Key: ID, Value: Code

            // 3. Get Type Education for Note Count (Assuming only 1 type is requested)
            $typeEducation = \App\Models\TypeEducation::find($typeEducationId);
            $cantNotes = $typeEducation ? $typeEducation->cantNotes : 4;

            $exportData = [];
            $headers = [];
            $nro = 1;

            // 4. Optimization: Get IDs needed to fetch students in one go
            // (Optional: If the dataset is massive, we can chunk. For now, we load per assignment).

            foreach ($assignments as $assignment) {
                // Parse Subject IDs
                $subjectIds = array_filter(array_map('trim', explode(',', $assignment->subject_ids)));

                if (empty($subjectIds)) continue;

                // 5. Fetch Students for this specific Grade/Section
                // Eager Load Notes ONLY for the relevant subjects to save memory
                $students = \App\Models\Student::query()
                    ->select('id', 'full_name', 'identity_document', 'grade_id', 'section_id', 'pdf', 'solvencyCertificate', 'boletin_active')
                    ->where('company_id', $companyId)
                    ->where('grade_id', $assignment->grade_id)
                    ->where('section_id', $assignment->section_id)
                    ->where('is_active', true) // Best practice
                    ->whereDoesntHave('withdrawal') // Exclude withdrawn
                    ->with(['notes' => function ($q) use ($subjectIds) {
                        $q->select('student_id', 'subject_id', 'json')
                            ->whereIn('subject_id', $subjectIds);
                    }])
                    ->orderBy('full_name') // Let SQL do the sorting
                    ->get();

                // 6. Process Students
                foreach ($students as $student) {

                    // Map notes for quick access: [subject_id => json_array]
                    $studentNotes = $student->notes->keyBy('subject_id');

                    $row = [
                        'nro' => $nro++,
                        'grade' => $assignment->grade->name,
                        'section' => $assignment->section->name,
                        'identity_document' => $student->identity_document,
                        'full_name' => $student->full_name,
                        'pdf' => $student->pdf == 1 ? 1 : '',
                        'solvencyCertificate' => $student->solvencyCertificate == 1 ? 1 : '',
                        'boletin_active' => $student->boletin_active == 1 ? 1 : '',
                    ];

                    // Process Grades
                    for ($i = 1; $i <= $cantNotes; $i++) {
                        foreach ($subjectIds as $subId) {
                            if (!isset($allSubjects[$subId])) continue;

                            $subjectCode = $allSubjects[$subId];
                            $headerKey = "{$subjectCode}{$i}";

                            // Build Headers Dynamically
                            $gradeName = $assignment->grade->name;
                            if (!isset($headers[$gradeName])) {
                                $headers[$gradeName] = [];
                            }
                            if (!in_array($headerKey, $headers[$gradeName])) {
                                $headers[$gradeName][] = $headerKey;
                            }

                            // Extract Note
                            $noteValue = null;
                            if (isset($studentNotes[$subId])) {
                                $jsonNotes = json_decode($studentNotes[$subId]->json, true);
                                $noteValue = $jsonNotes[$i] ?? null;
                            }

                            $row[$headerKey] = $noteValue;
                        }
                    }

                    // Add to main collection (Using a composite key to merge duplicates if student appears in multiple assignments)
                    $key = $student->identity_document;
                    if (!isset($exportData[$key])) {
                        $exportData[$key] = $row;
                    } else {
                        // Merge new subject grades into existing student row
                        $exportData[$key] = array_merge($exportData[$key], $row);
                    }
                }
            }

            // 7. Sort Headers
            foreach ($headers as $grade => $cols) {
                sort($headers[$grade]);
            }

            // 8. Prepare Final List (Sorting by Last Name via PHP as requested)
            $finalList = collect($exportData)->sortBy(function ($student) {
                $nameParts = explode(',', $student['full_name']);
                $lastName = trim($nameParts[0] ?? '');
                // Assuming normalizeSpanishString is a helper you have
                return function_exists('normalizeSpanishString') ? normalizeSpanishString($lastName) : $lastName;
            })->values()->toArray();

            if (count($finalList) > 0) {
                $excel = \Maatwebsite\Excel\Facades\Excel::raw(new \App\Exports\ConsolidatedExport($finalList, $headers, $typeEducationId), \Maatwebsite\Excel\Excel::XLSX);
                $excelBase64 = base64_encode($excel);
                return response()->json(['code' => 200, 'excel' => $excelBase64]);
            } else {
                return response()->json(['code' => 500, 'message' => 'No se han cargado alumnos']);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile()
            ]);
        }
    }

    public function savefiles(Request $request)
    {
        $field = $request->input('field');

        // El campo llega desde el request y luego se asigna con $student->$field, lo que
        // permitiría escribir CUALQUIER columna (password, is_active, user_id...).
        // Solo se aceptan las dos que realmente usa la carga de archivos.
        if (! array_key_exists($field, self::ALLOWED_FILE_FIELDS)) {
            return response()->json([
                'code' => 422,
                'message' => 'El campo indicado no es válido',
            ], 422);
        }

        // Cada campo acepta solo su tipo: la foto una imagen, el boletín un PDF.
        // La regla mimetypes mira el contenido real del archivo, no su extensión.
        $rules = self::ALLOWED_FILE_FIELDS[$field];

        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:' . self::MAX_FILE_KB,
                'mimetypes:' . implode(',', $rules['mimetypes']),
            ],
        ]);

        if ($validator->fails()) {
            $name = $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : '';

            return response()->json([
                'code' => 422,
                'message' => trim("El archivo $name no es válido: se espera {$rules['label']}, de hasta "
                    . (self::MAX_FILE_KB / 1024) . ' MB.'),
            ], 422);
        }

        // El colegio llega desde el frontend (store de autenticación), igual que en el resto
        // del módulo: el superadmin no tiene company_id propio y elige con cuál trabaja.
        $companyId = $request->input('company_id');

        if (empty($companyId)) {
            return response()->json([
                'code' => 422,
                'message' => 'Falta el colegio (company_id)',
            ], 422);
        }

        // Quien sí está atado a un colegio solo puede escribir en el suyo, sin importar
        // qué company_id mande. El superadmin (sin company_id) opera sobre el que eligió.
        $userCompanyId = $request->user()?->company_id;

        if (! empty($userCompanyId) && (string) $userCompanyId !== (string) $companyId) {
            return response()->json([
                'code' => 403,
                'message' => 'No puedes cargar archivos de otro colegio',
            ], 403);
        }

        $responseMessages = []; // Aquí guardaremos los mensajes de cada archivo

        // Verificar si los archivos han sido subidos
        if ($request->hasFile('file')) {

            try {

                // Obtener los archivos
                $file = $request->file('file');

                // Obtener el nombre del archivo sin extensión
                $fileNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                // Buscar el estudiante con ese documento de identidad dentro del colegio.
                // La cédula solo es única por colegio (índice unique_identity_per_company),
                // así que sin este filtro el archivo podía terminar en el alumno homónimo
                // de otra institución.
                $student = Student::where('identity_document', $fileNameWithoutExtension)
                    ->where('company_id', $companyId)
                    ->first();

                // Si el estudiante existe, actualizamos el campo 'photo'
                if ($student) {
                    // Guarda el archivo nuevo y borra el anterior para no dejar huérfanos.
                    $student->replaceFile($field, $file);

                    // Agregar mensaje de éxito al array
                    $responseMessages[] = [
                        'file' => $file->getClientOriginalName(),
                        'message' => 'Archivo guardado y foto actualizada correctamente',
                    ];
                } else {
                    // Si el estudiante no existe, agregar mensaje de error
                    $responseMessages[] = [
                        'file' => $file->getClientOriginalName(),
                        'message' => 'Estudiante no encontrado para ese documento de identidad',
                    ];
                }
            } catch (\Exception $e) {
                // Capturar excepciones y agregar mensaje de error al array
                $responseMessages[] = [
                    'file' => $file->getClientOriginalName(),
                    'message' => 'Error al guardar el archivo: ' . $e->getMessage(),
                ];
            }

            // Responder con los mensajes de cada archivo
            return response()->json([
                'code' => 200,
                'messages' => $responseMessages,
            ], 200);
        } else {
            return response()->json(['message' => 'No se han enviado archivos'], 400);
        }
    }

    /**
     * Apaga las banderas de documentos de todos los alumnos del colegio: notas (pdf),
     * boletín (boletin_active) y solvencia (solvencyCertificate).
     *
     * Se usa al cerrar el año escolar para dejar a todos en cero y volver a habilitarlos
     * a medida que se vayan solventando.
     */
    public function resetOptionDownloadPdf(Request $request)
    {
        $companyId = $request->input('company_id');

        // Sin colegio la actualización alcanzaría a los alumnos de todas las empresas.
        if (empty($companyId)) {
            return response()->json(['code' => 422, 'message' => 'Falta el colegio (company_id)']);
        }

        try {
            DB::beginTransaction();

            // Una sola sentencia en vez de recorrer y guardar alumno por alumno.
            // Se incluye a los retirados: tampoco deben conservar documentos habilitados.
            $updated = Student::where('company_id', $companyId)->update([
                'pdf' => 0,
                'boletin_active' => 0,
                'solvencyCertificate' => 0,
            ]);

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => "Se reiniciaron notas, boletín y solvencia de {$updated} estudiantes",
            ]);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }


    public function downloadConsolidatedPocentage(Request $request)
    {
        try {
            $companyId = $request->input('company_id');
            // Inicializamos la variable, pero puede cambiar si buscamos al profesor
            $typeEducationId = $request->input('type_education_id');
            $teacherId = $request->input('teacher_id');

            // LÓGICA DE AUTODETECCIÓN
            // Si no mandan el tipo de educación, pero SÍ mandan el profesor, lo buscamos.
            if (empty($typeEducationId) && !empty($teacherId)) {
                $teacher = \App\Models\Teacher::find($teacherId);
                if ($teacher) {
                    $typeEducationId = $teacher->type_education_id;
                } else {
                    return response()->json(['code' => 404, 'message' => 'Profesor no encontrado']);
                }
            }

            // Validación de seguridad: A estas alturas DEBE haber un type_education_id
            if (empty($typeEducationId)) {
                return response()->json(['code' => 422, 'message' => 'Falta el ID del tipo de educación']);
            }

            // --- AHORA SÍ, LA CONSULTA QUE YA TENÍAMOS ---

            // 1. Obtener Asignaciones
            $assignmentsQuery = \App\Models\TeacherComplementary::query()
                ->with([
                    'grade:id,name',
                    'section:id,name',
                    'teacher' => function ($q) use ($companyId, $typeEducationId) {
                        $q->select('id', 'company_id', 'type_education_id')
                            ->where('company_id', $companyId)
                            ->where('type_education_id', $typeEducationId);
                    }
                ])
                ->whereHas('teacher', function ($q) use ($companyId, $typeEducationId) {
                    $q->where('company_id', $companyId)
                        ->where('type_education_id', $typeEducationId);
                });

            // Aplicar filtro si tenemos el ID específico
            if (!empty($teacherId)) {
                $assignmentsQuery->where('teacher_id', $teacherId);
            }

            $assignments = $assignmentsQuery->get();


            // 2. Optimización: Obtener solo las materias de esta empresa Y de este tipo de educación
            $allSubjects = \App\Models\Subject::query()
                ->where('company_id', $companyId)
                ->where('type_education_id', $typeEducationId) // <--- Filtro clave que faltaba
                ->where('is_active', true) // <--- Buena práctica: solo materias activas
                ->pluck('code', 'id'); // Key: ID, Value: Code

            // 3. Get Type Education for Note Count (Assuming only 1 type is requested)
            $typeEducation = \App\Models\TypeEducation::find($typeEducationId);
            $cantNotes = $typeEducation ? $typeEducation->cantNotes : 4;

            $exportData = [];
            $headers = [];
            $nro = 1;

            // 4. Optimization: Get IDs needed to fetch students in one go
            // (Optional: If the dataset is massive, we can chunk. For now, we load per assignment).

            foreach ($assignments as $assignment) {
                // Parse Subject IDs
                $subjectIds = array_filter(array_map('trim', explode(',', $assignment->subject_ids)));

                if (empty($subjectIds)) continue;

                // 5. Fetch Students for this specific Grade/Section
                // Eager Load Notes ONLY for the relevant subjects to save memory
                $students = \App\Models\Student::query()
                    ->select('id', 'full_name', 'identity_document', 'grade_id', 'section_id', 'pdf', 'solvencyCertificate', 'boletin_active')
                    ->where('company_id', $companyId)
                    ->where('grade_id', $assignment->grade_id)
                    ->where('section_id', $assignment->section_id)
                    ->where('is_active', true) // Best practice
                    ->whereDoesntHave('withdrawal') // Exclude withdrawn
                    ->with(['notes' => function ($q) use ($subjectIds) {
                        $q->select('student_id', 'subject_id', 'json')
                            ->whereIn('subject_id', $subjectIds);
                    }])
                    ->orderBy('full_name') // Let SQL do the sorting
                    ->get();

                // 6. Process Students
                foreach ($students as $student) {

                    // Map notes for quick access: [subject_id => json_array]
                    $studentNotes = $student->notes->keyBy('subject_id');

                    $row = [
                        'nro' => $nro++,
                        'grade' => $assignment->grade->name,
                        'section' => $assignment->section->name,
                        'identity_document' => $student->identity_document,
                        'full_name' => $student->full_name,
                        'pdf' => $student->pdf == 1 ? 1 : '',
                        'solvencyCertificate' => $student->solvencyCertificate == 1 ? 1 : '',
                        'boletin_active' => $student->boletin_active == 1 ? 1 : '',
                    ];

                    // Process Grades
                    for ($i = 1; $i <= $cantNotes; $i++) {
                        foreach ($subjectIds as $subId) {
                            if (!isset($allSubjects[$subId])) continue;

                            $subjectCode = $allSubjects[$subId];
                            $headerKey = "{$subjectCode}{$i}";

                            // Build Headers Dynamically
                            $gradeName = $assignment->grade->name;
                            if (!isset($headers[$gradeName])) {
                                $headers[$gradeName] = [];
                            }
                            if (!in_array($headerKey, $headers[$gradeName])) {
                                $headers[$gradeName][] = $headerKey;
                            }

                            // Extract Note
                            $noteValue = null;
                            if (isset($studentNotes[$subId])) {
                                $jsonNotes = json_decode($studentNotes[$subId]->json, true);
                                $noteValue = $jsonNotes[$i] ?? null;
                            }

                            $row[$headerKey] = $noteValue;
                        }
                    }

                    // Add to main collection (Using a composite key to merge duplicates if student appears in multiple assignments)
                    $key = $student->identity_document;
                    if (!isset($exportData[$key])) {
                        $exportData[$key] = $row;
                    } else {
                        // Merge new subject grades into existing student row
                        $exportData[$key] = array_merge($exportData[$key], $row);
                    }
                }
            }

            // 7. Sort Headers
            foreach ($headers as $grade => $cols) {
                sort($headers[$grade]);
            }

            // 8. Prepare Final List (Sorting by Last Name via PHP as requested)
            $finalList = collect($exportData)->sortBy(function ($student) {
                $nameParts = explode(',', $student['full_name']);
                $lastName = trim($nameParts[0] ?? '');
                // Assuming normalizeSpanishString is a helper you have
                return function_exists('normalizeSpanishString') ? normalizeSpanishString($lastName) : $lastName;
            })->values()->toArray();

            if (count($finalList) > 0) {
                $excel = \Maatwebsite\Excel\Facades\Excel::raw(new \App\Exports\ConsolidatedExportPercentage($finalList, $headers, $typeEducationId), \Maatwebsite\Excel\Excel::XLSX);
                $excelBase64 = base64_encode($excel);
                return response()->json(['code' => 200, 'excel' => $excelBase64]);
            } else {
                return response()->json(['code' => 500, 'message' => 'No se han cargado alumnos']);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile()
            ]);
        }
    }


    public function deleteAllStudentGradeScores(Request $request)
    {
        try {
            DB::beginTransaction();

            // Eliminar todos los registros de la tabla 'notes'
            DB::table('notes')->delete();

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => 'Todas las notas de los estudiantes han sido eliminadas exitosamente.'
            ]);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json([
                'code' => 500,
                'message' => 'Error al eliminar las notas: ' . $th->getMessage()
            ]);
        }
    }
}
