<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedExport;
use App\Http\Requests\Teacher\TeacherStoreRequest;
use App\Http\Resources\Teacher\TeacherFormResource;
use App\Http\Resources\Teacher\TeacherListResource;
use App\Http\Resources\Teacher\TeacherPlanningResource;
use App\Jobs\BrevoProcessSendEmail;
use App\Models\Teacher;
use App\Repositories\CompanyRepository;
use App\Repositories\JobPositionRepository;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TeacherPlanningRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class TeacherController extends Controller
{
    public function __construct(
        protected TeacherRepository $teacherRepository,
        protected TypeEducationRepository $typeEducationRepository,
        protected JobPositionRepository $jobPositionRepository,
        protected SectionRepository $sectionRepository,
        protected TeacherComplementaryRepository $teacherComplementaryRepository,
        protected SubjectRepository $subjectRepository,
        protected StudentRepository $studentRepository,
        protected TeacherPlanningRepository $teacherPlanningRepository,
        protected CompanyRepository $companyRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->teacherRepository->list($request->all());
            $tableData = TeacherListResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function create(Request $request)
    {
        try {
            $typeEducations = $this->typeEducationRepository->list(
                request: [
                    'typeData' => 'all',
                ],
                with: ['grades.subjects']
            )->map(function ($value) use ($request) {
                return [
                    'value' => $value->id,
                    'title' => $value->name,
                    'grades' => $value->grades->where('company_id', $request['company_id'])->values()->map(function ($value2) {
                        return [
                            'value' => $value2->id,
                            'title' => $value2->name,
                            'subjects' => $value2->subjects->map(function ($value3) {
                                return [
                                    'value' => $value3->id,
                                    'title' => $value3->name,
                                ];
                            }),
                        ];
                    }),
                ];
            });

            $jobPositions = $this->jobPositionRepository->selectList();
            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'typeEducations' => $typeEducations,
                'jobPositions' => $jobPositions,
                'sections' => $sections,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(TeacherStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo', 'complementaries']);

            if (empty($post['password'])) {
                unset($post['password']);
            }

            $data = $this->teacherRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $file->store('company_' . $data->company_id . '/teachers/teacher_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $photo;
                $data->save();
            }

            //COMPLEMENTARIES
            $complementaries = json_decode($request->input('complementaries'), 1);
            $arrayIds = collect($complementaries)->pluck('id');
            $this->teacherRepository->deleteArrayComplementaries($arrayIds, $data);

            if ($complementaries) {
                foreach ($complementaries as $key => $value) {
                    $subjectsArray = collect($value['subjects'])->pluck('value')->toArray();
                    $subjectsArray = implode(', ', $subjectsArray);

                    $this->teacherComplementaryRepository->store([
                        'id' => $value['id'] ?? null,
                        'teacher_id' => $data->id,
                        'grade_id' => $value['grade_id'],
                        'section_id' => $value['section_id'],
                        'subject_ids' => $subjectsArray,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro agregada correctamente']);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $typeEducations = $this->typeEducationRepository->list(
                request: [
                    'typeData' => 'all',
                ],
                with: ['grades.subjects']
            )->map(function ($value) use ($request) {
                return [
                    'value' => $value->id,
                    'title' => $value->name,
                    'grades' => $value->grades->where('company_id', $request['company_id'])->values()->map(function ($value2) {
                        return [
                            'value' => $value2->id,
                            'title' => $value2->name,
                            'subjects' => $value2->subjects->map(function ($value3) {
                                return [
                                    'value' => $value3->id,
                                    'title' => $value3->name,
                                ];
                            }),
                        ];
                    }),
                ];
            });

            $jobPositions = $this->jobPositionRepository->selectList();
            $sections = $this->sectionRepository->selectList();

            $teacher = $this->teacherRepository->find($id);
            $form = new TeacherFormResource($teacher);

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeEducations' => $typeEducations,
                'jobPositions' => $jobPositions,
                'sections' => $sections,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(TeacherStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo', 'complementaries', 'password']);

            $data = $this->teacherRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $file->store('company_' . $data->company_id . '/teachers/teacher_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $photo;
                $data->save();
            }

            //COMPLEMENTARIES
            $complementaries = json_decode($request->input('complementaries'), 1);
            $arrayIds = collect($complementaries)->pluck('id');
            $this->teacherRepository->deleteArrayComplementaries($arrayIds, $data);

            if ($complementaries) {
                foreach ($complementaries as $key => $value) {
                    $subjectsArray = collect($value['subjects'])->pluck('value')->toArray();
                    $subjectsArray = implode(', ', $subjectsArray);

                    $this->teacherComplementaryRepository->store([
                        'id' => $value['id'] ?? null,
                        'teacher_id' => $data->id,
                        'grade_id' => $value['grade_id'],
                        'section_id' => $value['section_id'],
                        'subject_ids' => $subjectsArray,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro modificado correctamente']);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $teacher = $this->teacherRepository->find($id);
            if ($teacher) {
                $teacher->delete();
                $msg = 'Registro eliminado correctamente';
            } else {
                $msg = 'El registro no existe';
            }
            DB::commit();

            return response()->json(['code' => 200, 'message' => $msg]);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = $this->teacherRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Teacher ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function downloadConsolidated(Request $request, $id)
    {
        try {
            $teacherComplementaries = $this->teacherComplementaryRepository->list([
                'typeData' => 'all',
                'teacher_id' => $id,
            ], ['grade', 'section']);

            $teacher = $this->teacherRepository->find($id);

            $subjectsData = $this->subjectRepository->list([
                'typeData' => 'all',
                'company_id' => $teacher->company_id,
            ]);

            $listStudentAll = $this->studentRepository->list([
                'typeData' => 'all',
                'company_id' => $teacher->company_id,
            ], ['notes']);

            $students = [];
            $nro = 1;

            // Construir los encabezados
            $headers = [];

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

                        // Verifica si el estudiante ya existe en el array
                        if (isset($students[$value2->identity_document])) {
                            // Si ya existe, fusiona los datos
                            $existingStudentData = $students[$value2->identity_document];

                            // Fusionamos las notas
                            foreach ($studentData as $key => $newValue) {
                                if ($key !== 'identity_document' && $key !== 'full_name' && $key !== 'nro' && $key !== 'grade' && $key !== 'section') {
                                    // Si ya existe una nota, la mantenemos (no sobrescribir)
                                    if (! isset($existingStudentData[$key]) || $existingStudentData[$key] === null) {
                                        $existingStudentData[$key] = $newValue;
                                    }
                                }
                            }

                            // Actualiza el array con la nueva información combinada
                            $students[$value2->identity_document] = $existingStudentData;
                        } else {
                            // Si no existe, simplemente agrega el estudiante
                            $students[$value2->identity_document] = $studentData;
                        }
                    }
                }
            }




            // Ordenando los header de las materias
            $headers = collect($headers)->map(function ($subjects) {
                sort($subjects);

                return $subjects;
            });



            if (count($students) > 0) {
                $excel = Excel::raw(new ConsolidatedExport($students, $headers), \Maatwebsite\Excel\Excel::XLSX);

                $excelBase64 = base64_encode($excel);

                return response()->json(['code' => 200, 'excel' => $excelBase64]);
            } else {
                return response()->json(['code' => 500, 'message' => 'No se han cargado alumnos']);
            }
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()]);
        }
    }

    public function resetPassword($id)
    {
        try {
            DB::beginTransaction();

            // Buscar al usuario por ID
            $teacher = $this->teacherRepository->find($id);
            if (! $teacher) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // Actualizar la contraseña
            $teacher->password = 123456;
            $teacher->save();

            DB::commit();

            $user_admin = auth()->user();
            $company_name = $teacher->company?->name;

            // Enviar el correo usando el job de Brevo
            BrevoProcessSendEmail::dispatch(
                emailTo: [
                    [
                        "name" => $teacher->full_name,
                        "email" => $teacher->email,
                    ]
                ],
                subject: "Tu contraseña ha sido reiniciada por el administrador",
                templateId: 3,  // El ID de la plantilla de Brevo que quieres usar
                params: [
                    "full_name" => $teacher->full_name, 
                    "user_admin" => $user_admin->full_name, 
                    "new_password" => "123456",
                    "company_name" => $company_name, 
                ],
            );

            return response()->json(['code' => 200, 'message' => 'Contraseña reinicida con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function planning($id = null)
    {
        $data = $this->teacherRepository->find($id, ['complementaries']);
        $data = new TeacherPlanningResource($data);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function planningStore(Request $request)
    {
        try {

            DB::beginTransaction();


            //obtengo los ids de los archivos
            $arrayIds = [];
            $fileCount = $request->input('files_cant');


            for ($i = 0; $i < $fileCount; $i++) {
                $fileId = $request->input('file_id_' . $i);
                if ($fileId) {
                    $arrayIds[] = $fileId;
                }
            }

            $groupedData = [];

            $fileCount = $request->input('files_cant'); // Obtén la cantidad de archivos

            for ($i = 0; $i < $fileCount; $i++) {
                // Agrupar los valores correspondientes por índice
                $groupedData[] = [
                    'name' => $request->input("file_name_{$i}"),
                    'id' => $request->input("file_id_{$i}"),
                    'grade_id' => $request->input("file_grade_id_{$i}"),
                    'section_id' => $request->input("file_section_id_{$i}"),
                    'subject_id' => $request->input("file_subject_id_{$i}"),
                ];
            }

            $teacher = $this->teacherRepository->find($request->input('teacher_id'));

            $this->teacherPlanningRepository->deleteArray($arrayIds, $teacher->id);

            foreach ($groupedData as $key => $value) {
                $teacherPlanning = $this->teacherPlanningRepository->store([
                    'id' => $value['id'],
                    'teacher_id' => $teacher->id,
                    'name' => $value['name'],
                    'grade_id' => $value['grade_id'],
                    'section_id' => $value['section_id'],
                    'subject_id' => $value['subject_id'],
                ]);

                if ($request->file("file_file_{$key}")) {
                    $file =  $request->file("file_file_{$key}");
                    $path = $file->store('company_' . $teacher->company_id . '/teachers/teacher_' . $request->input('teacher_id') . '/plannings' . $request->input('file_file_{$key}'), 'public');
                    $teacherPlanning->path = $path;
                    $teacherPlanning->save();
                }
            }

            DB::commit();

            $data = new TeacherPlanningResource($teacher);

            return response()->json(['code' => 200, 'message' => 'Registros actualizados con éxito', 'data' => $data]);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function updateOrder(Request $request)
    {
        try {

            DB::beginTransaction();
            $teachers = $request->input('teachers'); // Array de teachers con el nuevo orden

            foreach ($teachers as $index => $teacher) {
                Teacher::where('id', $teacher['id'])->update(['order' => $index]);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Orden actualizado correctamente']);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()]);
        }
    }

    public function resetPlanifications(Request $request)
    {
        try {
            DB::beginTransaction();

            // Eliminar todas las planificaciones
            $this->teacherPlanningRepository->deleteAll($request->company_id);

            DB::commit();

            $auth = auth()->user();
            $company_name = $this->companyRepository->find($request->company_id)->name;

            // Enviar el correo usando el job de Brevo
            BrevoProcessSendEmail::dispatch(
                emailTo: [
                    [
                        "name" => $auth->full_name,
                        "email" => $auth->email,
                    ]
                ],
                subject: "Las planificaciones han sido reiniciadas (eliminadas)",
                templateId: 2,  // El ID de la plantilla de Brevo que quieres usar
                params: [
                    "full_name" => $auth->full_name, 
                    "company_name" => $company_name, 
                ],
            );

            return response()->json(['code' => 200, 'message' => 'Planificaciones reinicidas con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
