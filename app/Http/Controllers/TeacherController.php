<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedExport;
use App\Http\Requests\Teacher\TeacherStoreRequest;
use App\Http\Resources\Teacher\TeacherFormResource;
use App\Http\Resources\Teacher\TeacherListResource;
use App\Http\Resources\Teacher\TeacherPlanningResource;
use App\Models\Teacher;
use App\Repositories\GradeRepository;
use App\Repositories\JobPositionRepository;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TeacherPlanningRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TypeEducationRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;


class TeacherController extends Controller
{

    public function __construct(
        private TeacherRepository $teacherRepository,
        private JobPositionRepository $jobPositionRepository,
        private TypeEducationRepository $typeEducationRepository,
        private SubjectRepository $subjectRepository,
        private SectionRepository $sectionRepository,
        private GradeRepository $gradeRepository,
        private TeacherComplementaryRepository $teacherComplementaryRepository,
        private TeacherPlanningRepository $teacherPlanningRepository,
        private StudentRepository $studentRepository,
    ) {}

    public function list(Request $request)
    {
        $data = $this->teacherRepository->list($request->all());
        $teachers = TeacherListResource::collection($data);

        return [
            'tableData' => $teachers,
            'lastPage' => $data->lastPage(),
            'totalData' => $data->total(),
            'totalPage' => $data->perPage(),
            'currentPage' => $data->currentPage(),
        ];
    }

    public function dataForm($action = 'create', $id = null)
    {
        $data = null;
        if ($id) {
            $data = $this->teacherRepository->find($id);
            $data = new TeacherFormResource($data);
        }

        $jobPositions = $this->jobPositionRepository->selectList();
        $typeEducations = $this->typeEducationRepository->list(
            request: [
                "typeData" => "all",
            ],
            with: ["grades.subjects"]
        )->map(function ($value) {
            return [
                "value" => $value->id,
                "title" => $value->name,
                "grades" => $value->grades->map(function ($value2) {
                    return [
                        "value" => $value2->id,
                        "title" => $value2->name,
                        "subjects" => $value2->subjects->map(function ($value3) {
                            return [
                                "value" => $value3->id,
                                "title" => $value3->name,
                            ];
                        }),
                    ];
                }),
            ];
        });

        $sections = $this->sectionRepository->selectList();

        return response()->json([
            'form' => $data,
            'jobPositions' => $jobPositions,
            'typeEducations' => $typeEducations,
            'sections' => $sections,
        ]);
    }

    public function store(TeacherStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo', 'complementaries']);
            if (empty($post["password"])) {
                unset($post["password"]);
            }
            $data = $this->teacherRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $request->root() . '/storage/' . $file->store('company_' . $data->company_id . '/teachers/teacher_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $photo;
            }

            $data->save();

            $complementaries = json_decode($request->input('complementaries'), 1);
            if (count($complementaries) > 0) {
                foreach ($complementaries as $key => $value) {
                    if ($value['delete'] == 1) {
                        $this->teacherComplementaryRepository->delete($value['id']);
                    } else {
                        $subjectsArray = collect($value['subjects'])->pluck('value')->toArray();

                        $this->teacherComplementaryRepository->store([
                            'id' => $value['id'],
                            'grade_id' => $value['grade_id'],
                            'teacher_id' => $data->id,
                            'section_id' => $value['section_id'],
                            'subject_ids' => implode(', ', $subjectsArray),
                            'id' => $value['id'],
                        ]);
                    }
                }
            }

            $data = new TeacherFormResource($data);

            $msg = 'agregado';
            if (!empty($request['id'])) {
                $msg = 'modificado';
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' correctamente', 'data' => $data]);
        } catch (Exception $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()], 500);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $data = $this->teacherRepository->find($id);
            if ($data) {
                $data->delete();
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
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function changeState(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = $this->teacherRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' con éxito']);
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
            $teacher = $this->teacherRepository->find($request->input('teacher_id'), ['complementaries']);

            for ($i = 0; $i < $request->input('files_cant'); $i++) {
                if ($request->input('file_delete_' . $i) == 1) {
                    $this->teacherPlanningRepository->delete($request->input('file_id_' . $i));
                } else {

                    $teacherPlanning = $this->teacherPlanningRepository->store([
                        'id' => $request->input('file_id_' . $i) === 'null' ? null : $request->input('file_id_' . $i),
                        'teacher_id' => $teacher->id,
                        'grade_id' => $request->input('file_grade_id_' . $i),
                        'section_id' => $request->input('file_section_id_' . $i),
                        'subject_id' => $request->input('file_subject_id_' . $i),
                        'path' => $request->input('file_file_' . $i),
                        'name' => $request->input('file_name_' . $i),
                    ]);

                    if ($request->file('file_file_' . $i)) {
                        $file = $request->file('file_file_' . $i);
                        $path = $request->root() . '/storage/' . $file->store('company_' . $teacher->company_id . '/teachers/teacher_' . $request->input('teacher_id') . '/plannings' . $request->input('file_file_' . $i), 'public');
                        $teacherPlanning->path = $path;
                    }
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
        $teachers = $request->input('teachers'); // Array de teachers con el nuevo orden


        foreach ($teachers as $index => $teacher) {
            Teacher::where('id', $teacher['id'])->update(['order' => $index]);
        }

        return response()->json(['message' => 'Orden actualizado correctamente']);
    }


    public function downloadConsolidated(Request $request, $id)
    {
        try {
            $teacherComplementaries = $this->teacherComplementaryRepository->list([
                "typeData" => "all",
                "teacher_id" => $id,
            ], ["grade", "section"]);


            $teacher = $this->teacherRepository->find($id);

            $subjectsData = $this->subjectRepository->list([
                "typeData" => "all",
                "company_id" => $teacher->company_id,
            ]);

            $students = [];
            $nro = 1;

            // Construir los encabezados
            $headers = [];


            foreach ($teacherComplementaries as $key => $value) {

                $list = $this->studentRepository->list([
                    "typeData" => "all",
                    "company_id" => $teacher->company_id,
                    "type_education_id" => $teacher->type_education_id,
                    "grade_id" => $value->grade_id,
                    "section_id" => $value->section_id,
                ], ["notes"]);

                $subjectIds = explode(',', $value->subject_ids);

                $filteredSubjects = $subjectsData->whereIn('id', $subjectIds);

                if (count($list) > 0) {
                    foreach ($list as $key2 => $value2) {
                        // Inicializa un array para los códigos de materias
                        $studentData = [
                            "nro" => $nro++,
                            "grade" => $value->grade->name,
                            "section" => $value->section->name,
                            "identity_document" => $value2->identity_document,
                            "full_name" => $value2->full_name,
                        ];

                        // Agregar códigos como keys basadas en la cantidad de notas
                        for ($i = 1; $i <= $teacher->typeEducation->cantNotes; $i++) {
                            foreach ($filteredSubjects as $subject) {

                                $code = "{$subject->code}{$i}";

                                // Verifica si ya existe un array para este grado
                                if (!isset($headers[$value->grade->name])) {
                                    $headers[$value->grade->name] = []; // Inicializa el array si no existe
                                }

                                // Agrega el código si no existe
                                if (!in_array($code, $headers[$value->grade->name])) {
                                    $headers[$value->grade->name][] = $code; // Agrega el código al grado correspondiente
                                }

                                // Intenta obtener las notas para el subject_id correspondiente
                                $notes = $value2->notes->where("subject_id", $subject->id)->first(); // Cambia aquí para usar el ID correcto

                                // Verifica si se encontraron notas y decodifica
                                if ($notes) {
                                    $notesArray = json_decode($notes->json, true); // Cambia a `true` para obtener un array asociativo

                                    // Asigna la nota correspondiente si existe
                                    if (isset($notesArray[$i])) { // Ajustar índice
                                        // Verifica si ya existe
                                        if (!isset($studentData["{$subject->code}{$i}"])) {
                                            $studentData["{$subject->code}{$i}"] = $notesArray[$i]; // Asigna la nota si no existe
                                        }
                                    } else {
                                        $studentData["{$subject->code}{$i}"] = "-"; // O cualquier valor predeterminado
                                    }
                                } else {
                                    // Si no se encontraron notas, asigna null
                                    $studentData["{$subject->code}{$i}"] = "-";
                                }
                            }
                        }

                        // if (!in_array($studentData["identity_document"], $students)) {
                            $students[] = $studentData; // Agrega el estudiante completo al array
                        // }
                    }
                }
            }


            // Convertir el array a una colección
            $studentsCollection = collect($students);

            // Eliminar duplicados por 'id'
            $students = $studentsCollection->unique('identity_document')->values()->all();


            // // Agrupar por 'grade' y ordenar todos los estudiantes por 'section' alfabéticamente
            // $groupedStudents = collect($students)
            //     ->groupBy('grade')
            //     ->map(function ($gradeGroup) {
            //         // Ordenar estudiantes por section alfabéticamente
            //         return $gradeGroup->sortBy('section');
            //     });

            // // Si deseas convertirlo a un array
            // return$groupedStudentsArray = $groupedStudents->toArray();


            if (count($students) > 0) {
                $excel = Excel::raw(new ConsolidatedExport($students, $headers), \Maatwebsite\Excel\Excel::XLSX);

                $excelBase64 = base64_encode($excel);
                return response()->json(['code' => 200, 'excel' => $excelBase64]);
            } else {

                return response()->json(['code' => 500, 'message' => "No se han cargado alumnos"]);
            }
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()]);
        }
    }
}
