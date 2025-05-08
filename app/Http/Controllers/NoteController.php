<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedExport;
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
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class NoteController extends Controller
{
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

    public function dataForm()
    {
        Cache::put('Cache_Grade', Grade::get(), now()->addMinutes(60));
        Cache::put('Cache_Section', Section::get(), now()->addMinutes(60));

        $typeEducations = $this->typeEducationRepository->selectList(select: ['cantNotes']);
        $teachers = Teacher::get()->map(function ($item) {
            return [
                'value' => $item->id,
                'title' => $item->full_name,
            ];
        });
        $blockData = BlockData::where('name', Constants::BLOCK_PAYROLL_UPLOAD)->first()->is_active;

        return response()->json([
            'typeEducations' => $typeEducations,
            'blockData' => $blockData,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            if ($request->hasFile('archive')) {
                $file = $request->file('archive');
                $import = Excel::toArray([], $file);

                $teacher_id = $request->teacher_id;

                $teacher = Teacher::with([
                    'complementaries',
                ])
                    ->find($teacher_id);

                $subjectsData = [];
                if ($teacher) {
                    foreach ($teacher->complementaries as $complementary) {
                        // Acceder a las materias relacionadas
                        $subjects = $complementary->subjects;

                        // Puedes hacer lo que necesites con las materias
                        foreach ($subjects as $subject) {
                            // echo $subject->name; // Mostrar el nombre de la materia, por ejemplo
                            $subjectsData[] = $subject;
                        }
                    }
                    // return $subjectsData;
                }

                $typeEducation = $this->typeEducationRepository->find($request->input('type_education_id'), ['grades.subjects']);

                $sheets = count($import);
                for ($j = 0; $j < $sheets; $j++) {


                    for ($i = 0; $i < $typeEducation->cantNotes; $i++) {
                        // Suponiendo que solo hay una hoja en el archivo Excel
                        $data = $import[$j];


                        // Obtener las claves y eliminarlas de $data
                        $keys = array_shift($data);
                        $formattedData = [];


                        foreach ($data as $row) {
                            $formattedRow = [];
                            foreach ($keys as $index => $key) {
                                $formattedRow[$key] = $row[$index] ?? null;
                            }
                            $formattedData[] = $formattedRow;
                        }



                        // $groupedCedulas = collect($formattedData)
                        //     ->filter(function ($item) {
                        //         return ! is_null($item['CÉDULA']); // Filtrar elementos con cédulas no nulas
                        //     })
                        //     ->groupBy('AÑO') // Agrupar por AÑO
                        //     ->map(function ($yearGroup) {
                        //         return $yearGroup->groupBy('SECCIÓN') // Agrupar por SECCIÓN dentro de cada AÑO
                        //             ->map(function ($sectionGroup) {
                        //                 return $sectionGroup->pluck('CÉDULA')->filter()->values(); // Extraer cédulas
                        //             });
                        //     });

                        // foreach ($groupedCedulas as $key => $value) {
                        //     // $grade = Grade::where("name", $key)->first();
                        //     $grade = $this->grade($key, 'name');
                        //     if ($grade) {
                        //         foreach ($value as $key2 => $value2) {
                        //             // $section = Section::where("name", trim($key2))->first();
                        //             $section = $this->section($key2, 'name');
                        //             if ($section) {
                        //                 $this->studentRepository->deleteDataArray([
                        //                     'company_id' => $request->input('company_id'),
                        //                     'identity_document' => $value2,
                        //                     'type_education_id' => $request->input('type_education_id'),
                        //                     'grade_id' => $grade->id,
                        //                     'section_id' => $section->id,
                        //                 ]);
                        //             }
                        //         }
                        //     }
                        // }

                        // Obtener todos los estudiantes cuyos cedulas NO están en el array

                        $formattedData = array_map(function ($item) {
                            return array_map('trim', $item); // Aplica trim a cada valor del item
                        }, $formattedData);


                        foreach ($formattedData as $kkk => $row) {
                            if (! empty($row['CÉDULA'])) {

                                $grade = $this->grade($row['AÑO'], 'name');
                                // $section = $this->section($row['SECCIÓN'], 'name');

                                $student = $this->studentRepository->searchOne([
                                    'identity_document' => $row['CÉDULA'],
                                ]);

                                $model = [
                                    'id' => $student ? $student->id : null,
                                    // 'company_id' => $request->input('company_id'),
                                    // 'type_education_id' => $request->input('type_education_id'),
                                    // 'grade_id' => $grade?->id,
                                    // 'section_id' => $section?->id,
                                    // 'identity_document' => $row['CÉDULA'],
                                    // 'full_name' => $row['NOMBRES Y APELLIDOS ESTUDIANTE'],
                                ];

                                if (isset($row['PDF'])) {
                                    $model['pdf'] = $row['PDF'] == 1 ? 1 : 0;
                                }

                                if ($student) {
                                    unset($model['password']);
                                }

                                // $student = $this->studentRepository->store($model); /// decomentar

                                //  $teacher->complementaries->where("grade_id",$grade->id);


                                if (!$teacher) {
                                    $subjectsData = [];
                                }
                                if (count($subjectsData) == 0) {

                                    $grade = $typeEducation->grades->where('id', $grade->id)->first();

                                    $subjectsData = $grade->subjects;
                                }


                                // return $subjectsData;


                                foreach ($subjectsData as $key => $sub) {
                                    $model2 = [
                                        'student_id' => $student->id,
                                        'subject_id' => $sub->id,
                                    ];

                                    $note = $this->noteRepository->searchOne($model2);
                                    $json = null;

                                    if ($note) {
                                        $json = json_decode($note->json, 1);
                                    }

                                    $model2 = [
                                        'id' => $note ? $note->id : null,
                                        'student_id' => $student->id,
                                        'subject_id' => $sub->id,
                                    ];

                                    for ($xx = 1; $xx <= $typeEducation->cantNotes; $xx++) {
                                        $json[$xx] = isset($row[$sub->code . $xx]) ? trim($row[$sub->code . $xx]) : (isset($json[$xx]) ? $json[$xx] : null);
                                    }

                                    $model2['json'] = json_encode($json);

                                    $this->noteRepository->store($model2);
                                }
                                // return 4444;
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registros guardados correctamente', 'data' => $data]);
        } catch (Exception $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()], 500);
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

    public function downloadAllConsolidated(Request $request)
    {
        try {

            $teacherComplementariesAll = $this->teacherComplementaryRepository->list([
                'typeData' => 'all',
            ], [
                'grade',
                'section',
                'teacher' => function ($query) use ($request) {
                    $query->where('type_education_id', $request->input('type_education_id'));
                },
            ]);

            $listStudentAll = $this->studentRepository->list([
                'typeData' => 'all',
                'company_id' => $request->input('company_id'),
            ], ['notes']);

            $teachers = $this->teacherRepository->list([
                'typeData' => 'all',
                'type_education_id' => $request->input('type_education_id'),
                'company_id' => $request->input('company_id'),
            ]);

            $subjectsData = $this->subjectRepository->list([
                'typeData' => 'all',
                'company_id' => $request->input('company_id'),
            ]);

            $students = [];
            $nro = 1;

            // Construir los encabezados
            $headers = [];

            foreach ($teachers as $key => $teacher) {

                $teacherComplementaries = $teacherComplementariesAll->where('teacher_id', $teacher->id);

                foreach ($teacherComplementaries as $key => $value) {

                    $list = $listStudentAll->where('company_id', $teacher->company_id)
                        ->where('type_education_id', $teacher->type_education_id)
                        ->where('grade_id', $value->grade_id)
                        ->where('section_id', $value->section_id);

                    $list = $list->sortBy('full_name');

                    $subjectIds = explode(',', $value->subject_ids);

                    $filteredSubjects = $subjectsData->whereIn('id', $subjectIds);

                    if (count($list) > 0) {
                        foreach ($list as $key2 => $value2) {
                            // Inicializa un array para los códigos de materias
                            $studentData = [
                                'nro' => $nro++,
                                'grade' => $value->grade->name,
                                'section' => $value->section->name,
                                'identity_document' => $value2->identity_document,
                                'full_name' => $value2->full_name,
                                'pdf' => $value2->pdf == 1 ? 1 : '',
                            ];

                            // Agregar códigos como keys basadas en la cantidad de notas
                            for ($i = 1; $i <= $teacher->typeEducation->cantNotes; $i++) {
                                foreach ($filteredSubjects as $subject) {

                                    $code = "{$subject->code}{$i}";

                                    // Verifica si ya existe un array para este grado
                                    if (! isset($headers[$value->grade->name])) {
                                        $headers[$value->grade->name] = []; // Inicializa el array si no existe
                                    }

                                    // Agrega el código si no existe
                                    if (! in_array($code, $headers[$value->grade->name])) {
                                        $headers[$value->grade->name][] = $code; // Agrega el código al grado correspondiente
                                    }

                                    // Intenta obtener las notas para el subject_id correspondiente
                                    $notes = $value2->notes->where('subject_id', $subject->id)->first(); // Cambia aquí para usar el ID correcto

                                    // Verifica si se encontraron notas y decodifica
                                    if ($notes) {
                                        $notesArray = json_decode($notes->json, true); // Cambia a `true` para obtener un array asociativo

                                        // Asigna la nota correspondiente si existe
                                        if (isset($notesArray[$i])) { // Ajustar índice
                                            // Verifica si ya existe
                                            if (! isset($studentData["{$subject->code}{$i}"])) {
                                                $studentData["{$subject->code}{$i}"] = $notesArray[$i]; // Asigna la nota si no existe
                                            }
                                        } else {
                                            $studentData["{$subject->code}{$i}"] = null; // O cualquier valor predeterminado
                                        }
                                    } else {
                                        // Si no se encontraron notas, asigna null
                                        $studentData["{$subject->code}{$i}"] = null;
                                    }
                                }
                            }

                            $students[] = $studentData; // Agrega el estudiante completo al array
                        }
                    }
                }
            }

            // Ordenando los header de las materias
            $headers = collect($headers)->map(function ($subjects) {
                sort($subjects);

                return $subjects;
            });

            // Convertir el array a una colección
            $studentsCollection = collect($students);

            // Agrupando por `identity_document` y `full_name`
            $students = $studentsCollection->reduce(function ($carry, $item) {
                $key = $item['identity_document'] . '|' . $item['full_name'];

                if (!isset($carry[$key])) {
                    $carry[$key] = $item; // Si no existe, añadir el item
                } else {
                    $carry[$key] = array_merge($carry[$key], $item); // Si existe, fusionar
                }

                return $carry;
            }, []);

            // Reemplaza tu código de ordenación actual con este:
            $students = collect($students)->sortBy(function ($student) {
                // Extraer el apellido que viene antes de la coma
                $nameParts = explode(',', $student['full_name']);
                $lastName = trim($nameParts[0] ?? '');

                // Usar Collator para ordenar correctamente según las reglas del español
                return normalizeSpanishString($lastName);
            })->values()->toArray();

            $type_education_id = $request->input('type_education_id');

            if (count($students) > 0) {
                $excel = Excel::raw(new ConsolidatedExport($students, $headers, $type_education_id), \Maatwebsite\Excel\Excel::XLSX);

                $excelBase64 = base64_encode($excel);

                return response()->json(['code' => 200, 'excel' => $excelBase64]);
            } else {

                return response()->json(['code' => 500, 'message' => 'No se han cargado alumnos']);
            }
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()]);
        }
    }

    public function savefiles(Request $request)
    {
        $field = $request->input('field');

        $responseMessages = []; // Aquí guardaremos los mensajes de cada archivo

        // Verificar si los archivos han sido subidos
        if ($request->hasFile('file')) {

            try {

                // Obtener los archivos
                $file = $request->file('file');

                // Obtener el nombre del archivo sin extensión
                $fileNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                // Buscar el estudiante con ese documento de identidad
                $student = Student::where('identity_document', $fileNameWithoutExtension)->first();

                // Si el estudiante existe, actualizamos el campo 'photo'
                if ($student) {
                    // Guardar el archivo en la carpeta 'students'
                    $path = $file->store('company_' . $student->company_id . '/student/student_' . $student->id . $request->input('file'), 'public');

                    // Actualizar el campo 'photo'
                    $student->$field = $path;
                    $student->save();

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

    public function resetOptionDownloadPdf(Request $request)
    {
        try {
            DB::beginTransaction();

            $request['typeData'] = 'all';
            $students = $this->studentRepository->list($request->all());
            foreach ($students as $key => $value) {
                $value->pdf = null;
                $value->save();
            }
            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Se ha reiniciado la opción de pdf en el consolidado']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
