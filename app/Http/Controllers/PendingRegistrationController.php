<?php

namespace App\Http\Controllers;

use App\Exports\PendingRegistrationExport;
use App\Http\Requests\PendingRegistration\PendingRegistrationStoreRequest;
use App\Http\Resources\PendingRegistration\PendingRegistrationPaginateResource;
use App\Http\Resources\Student\StudentSelectInifiniteResource;
use App\Repositories\PendingRegistrationAttemptRepository;
use App\Repositories\PendingRegistrationRepository;
use App\Repositories\PendingRegistrationStudentRepository;
use App\Repositories\PendingRegistrationSubjectRepository;
use App\Repositories\TermRepository;
use App\Repositories\TypeEducationRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;


class PendingRegistrationController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected PendingRegistrationRepository $pendingRegistrationRepository,
        protected PendingRegistrationStudentRepository $pendingRegistrationStudentRepository,
        protected PendingRegistrationSubjectRepository $pendingRegistrationSubjectRepository,
        protected TermRepository $termRepository,
        protected TypeEducationRepository $typeEducationRepository,
        protected PendingRegistrationAttemptRepository $pendingRegistrationAttemptRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->pendingRegistrationRepository->paginate($request->all());
            $tableData = PendingRegistrationPaginateResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function create(Request $request)
    {
        return $this->execute(function () use ($request) {

            $terms = $this->termRepository->selectList();

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

            return [
                'code' => 200,
                'terms' => $terms,
                'typeEducations' => $typeEducations,
            ];
        });
    }



    public function store(PendingRegistrationStoreRequest $request)
    {
        return $this->runTransaction(function () use ($request) {
            $term_id = $request->input("term_id");
            $code = $request->input("code");
            $students = $request->input("students");

            // Generar el nombre de la sección
            $term = $this->termRepository->find($term_id);
            $sectionName = $this->pendingRegistrationRepository->generateSectionName($term->name, $code);

            // Crear el registro de la sección en pending_registrations
            $pendingRegistration = $this->pendingRegistrationRepository->store([
                'company_id' => $request->input('company_id'),
                'term_id' => $term_id,
                'type_education_id' => $request->input('type_education_id'),
                'grade_id' => $request->input('grade_id'),
                'code' => $code,
                'section_name' => $sectionName,
            ]);

            // Registrar los estudiantes y sus materias
            foreach ($students as $studentData) {
                $studentId = $studentData['student_id'];
                $subjects = $studentData['subjects'];

                // Crear un registro en pending_registration_students
                $pendingRegistrationStudent = $this->pendingRegistrationStudentRepository->store([
                    'pending_registration_id' => $pendingRegistration->id,
                    'student_id' => $studentId,
                ]);

                // Registrar las materias pendientes en pending_registration_subjects
                $this->pendingRegistrationSubjectRepository->addSubjects(
                    $pendingRegistrationStudent->id,
                    $subjects
                );
            }

            return [
                'code' => 200,
                'message' => 'Materias pendientes registradas correctamente',
                'section' => $sectionName,
            ];
        }, debug: false);
    }



    public function edit(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {

            $terms = $this->termRepository->selectList();

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

            // Fetch the pending registration by ID
            $pendingRegistration = $this->pendingRegistrationRepository->find($id);

            if (!$pendingRegistration) {
                return [
                    'code' => 404,
                    'message' => 'Registro no encontrado',
                ];
            }

            // Fetch associated students and their subjects
            $studentsData = $this->pendingRegistrationStudentRepository
                ->getStudentsWithSubjectsByPendingRegistrationId($id)
                ->map(function ($item) {
                    return [
                        'student_id' => new StudentSelectInifiniteResource($item->student),
                        'subjects' => $item->subjects->pluck('subject_id')->toArray(),
                        'id' => $item->id,
                    ];
                })->toArray();

            // Prepare the response data
            $responseData = [
                'id' => $pendingRegistration->id,
                'company_id' => $pendingRegistration->company_id,
                'term_id' => $pendingRegistration->term_id,
                'type_education_id' => $pendingRegistration->type_education_id,
                'grade_id' => $pendingRegistration->grade_id,
                'section_name' => $pendingRegistration->section_name,
                'code' => $pendingRegistration->code,
                'students' => $studentsData,
            ];

            return [
                'code' => 200,
                'form' => $responseData,
                'terms' => $terms,
                'typeEducations' => $typeEducations,
            ];
        });
    }
    public function show(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {

            // Fetch the pending registration by ID
            $pendingRegistration = $this->pendingRegistrationRepository->find($id, ["term", "grade", "type_education"]);

            if (!$pendingRegistration) {
                return [
                    'code' => 404,
                    'message' => 'Registro no encontrado',
                ];
            }

            // Fetch associated students and their subjects
            $studentsData = $this->pendingRegistrationStudentRepository
                ->getStudentsWithSubjectsByPendingRegistrationId($id)
                ->map(function ($item) {
                    return [
                        'student_id' => new StudentSelectInifiniteResource($item->student),
                        'subjects' => $item->subjects->map(function ($item) {
                            return [
                                "id" => $item->subject_id,
                                "name" => $item->subject->name,
                            ];
                        }),
                        'id' => $item->id,
                    ];
                })->toArray();

            // Prepare the response data
            $responseData = [
                'id' => $pendingRegistration->id,
                'company_id' => $pendingRegistration->company_id,
                'term_id' => $pendingRegistration->term_id,
                'term_name' => $pendingRegistration->term?->name,
                'type_education_id' => $pendingRegistration->type_education_id,
                'type_education_name' => $pendingRegistration->type_education?->name,
                'grade_id' => $pendingRegistration->grade_id,
                'grade_name' => $pendingRegistration->grade?->name,
                'code' => $pendingRegistration->code,
                'section_name' => $pendingRegistration->section_name,
                'students' => $studentsData,
            ];

            return [
                'code' => 200,
                'form' => $responseData,
            ];
        });
    }

    public function update(PendingRegistrationStoreRequest $request, $id)
    {
        return $this->runTransaction(function () use ($request, $id) {

            $term_id = $request->input("term_id");
            $code = $request->input("code");

            // Generar el nombre de la sección
            $term = $this->termRepository->find($term_id);

            $pendingRegistration = $this->pendingRegistrationRepository->find($id);
            if (!$pendingRegistration) {
                return [
                    'code' => 404,
                    'message' => 'Registro no encontrado',
                ];
            }

            $sectionName = $this->pendingRegistrationRepository->generateSectionName($term->name, $code);

            $this->pendingRegistrationRepository->store([
                'id' => $id,
                'company_id' => $request->input('company_id'),
                'term_id' => $request->input('term_id'),
                'type_education_id' => $request->input('type_education_id'),
                'grade_id' => $request->input('grade_id'),
                'section_name' => $sectionName,
                'code' => $code,
            ]);

            $studentsInput = $request->input('students', []);
            $currentStudents = $this->pendingRegistrationStudentRepository
                ->getStudentsWithSubjectsByPendingRegistrationId($id)
                ->pluck('student_id', 'id')
                ->toArray();

            $newStudentIds = array_column($studentsInput, 'id');

            // Eliminar estudiantes que ya no están en la lista
            foreach ($currentStudents as $currentStudentId => $studentId) {
                if (!in_array($studentId, $newStudentIds)) {
                    $this->pendingRegistrationStudentRepository->delete($currentStudentId);
                }
            }

            // Procesar cada estudiante enviado
            foreach ($studentsInput as $studentData) {
                $studentId = $studentData['student_id'];
                $subjects = $studentData['subjects'];

                $existingStudent = $this->pendingRegistrationStudentRepository
                    ->findByPendingRegistrationAndStudent($id, $studentId);

                if ($existingStudent) {
                    // Sincronizar materias sin eliminar las que no han cambiado
                    $this->pendingRegistrationSubjectRepository->syncSubjects(
                        $existingStudent->id,
                        $subjects
                    );
                } else {
                    // Crear nuevo estudiante y agregar sus materias
                    $pendingRegistrationStudent = $this->pendingRegistrationStudentRepository->store([
                        'pending_registration_id' => $pendingRegistration->id,
                        'student_id' => $studentId,
                    ]);

                    $this->pendingRegistrationSubjectRepository->addSubjects(
                        $pendingRegistrationStudent->id,
                        $subjects
                    );
                }
            }

            return [
                'code' => 200,
                'message' => 'Registro actualizado correctamente',
                'section' => $pendingRegistration->section_name,
            ];
        }, debug: false);
    }

    public function delete($id)
    {
        return $this->runTransaction(function () use ($id) {
            $pendingRegistration = $this->pendingRegistrationRepository->find($id);
            if (!$pendingRegistration) {
                return [
                    'code' => 200,
                    'message' => 'El registro no existe',
                ];
            }

            // Eliminar todas las materias en una sola consulta
            $this->pendingRegistrationSubjectRepository->deleteByPendingRegistrationId($pendingRegistration->id);

            // Eliminar todos los estudiantes en una sola consulta
            $pendingRegistration->students()->delete();

            // Eliminar el registro principal
            $pendingRegistration->delete();

            return [
                'code' => 200,
                'message' => 'Registro eliminado correctamente',
            ];
        }, debug: false);
    }

    public function excelExport(Request $request)
    {
        $request["typeData"] = "all";
        $data = $this->pendingRegistrationRepository->paginate($request->all());

        $responseData = [];

        foreach ($data as $key => $pendingRegistration) {

            $pendingRegistration = $this->pendingRegistrationRepository->find($pendingRegistration->id);

            $attempts = $this->pendingRegistrationAttemptRepository->list([
                "pending_registration_id" => $pendingRegistration->id
            ], ['student', 'subject'])->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'student_id' => [
                        'value' => $attempt->student->id,
                        'title' => $attempt->student->full_name,
                    ],
                    'subject_id' => [
                        'value' => $attempt->subject->id,
                        'title' => $attempt->subject->name,
                    ],
                    'attempt_number' => $attempt->attempt_number, // Asegúrate de incluir este campo
                    'note' => $attempt->note,
                    'attempt_date' => Carbon::parse($attempt->attempt_date)->format("d-m-Y"),
                    'approved' => $attempt->approved,
                ];
            });


            // Fetch associated students and their subjects 
            $studentsData = $this->pendingRegistrationStudentRepository
                ->getStudentsWithSubjectsByPendingRegistrationId($pendingRegistration->id)
                ->map(function ($item) {
                    return [
                        'student_id' => json_decode(json_encode(new StudentSelectInifiniteResource($item->student)), 1),
                        'subjects' => $item->subjects->map(function ($item) {
                            return [
                                "id" => $item->subject_id,
                                "name" => $item->subject->name,
                            ];
                        }),
                        'id' => $item->id,
                    ];
                })->toArray();

            // Prepare the response data
            $responseData[] = [
                'id' => $pendingRegistration->id,
                'company_id' => $pendingRegistration->company_id,
                'company_name' => $pendingRegistration->company?->name,
                'term_id' => $pendingRegistration->term_id,
                'term_name' => $pendingRegistration->term?->name,
                'type_education_id' => $pendingRegistration->type_education_id,
                'type_education_name' => $pendingRegistration->type_education?->name,
                'grade_id' => $pendingRegistration->grade_id,
                'grade_name' => $pendingRegistration->grade?->name,
                'section_name' => $pendingRegistration->section_name,
                'code' => $pendingRegistration->code,
                'students' => $studentsData,
            ];
        }

        // return $attempts;

        $excel = Excel::raw(new PendingRegistrationExport($responseData, $attempts), \Maatwebsite\Excel\Excel::XLSX);

        $excelBase64 = base64_encode($excel);

        return response()->json(['code' => 200, 'excel' => $excelBase64]);
    }
}
