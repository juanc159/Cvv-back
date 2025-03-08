<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendingRegistration\PendingRegistrationStoreRequest;
use App\Http\Resources\PendingRegistration\PendingRegistrationFormResource;
use App\Http\Resources\PendingRegistration\PendingRegistrationPaginateResource;
use App\Repositories\PendingRegistrationRepository;
use App\Repositories\PendingRegistrationStudentRepository;
use App\Repositories\PendingRegistrationSubjectRepository;
use App\Repositories\TermRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class PendingRegistrationController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected PendingRegistrationRepository $pendingRegistrationRepository,
        protected PendingRegistrationStudentRepository $pendingRegistrationStudentRepository,
        protected PendingRegistrationSubjectRepository $pendingRegistrationSubjectRepository,
        protected TermRepository $termRepository,
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

    public function create()
    {
        return $this->execute(function () {});
    }



    public function store(PendingRegistrationStoreRequest $request)
    {
        return $this->runTransaction(function () use ($request) {
            $term_id = $request->input("term_id");
            $students = $request->input("students");

            // Generar el nombre de la sección
            $term = $this->termRepository->find($term_id);
            $sectionName = $this->pendingRegistrationRepository->generateSectionName($term->name);

            // Crear el registro de la sección en pending_registrations
            $pendingRegistration = $this->pendingRegistrationRepository->store([
                'company_id' => $request->input('company_id'),
                'term_id' => $term_id,
                'grade_id' => $request->input('grade_id'),
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



    public function edit($id)
    {
        return $this->execute(function () use ($id) {
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
                ->map(function ($student) {
                    return [
                        'student_id' => $student->student_id,
                        'subjects' => $student->subjects->pluck('subject_id')->toArray(),
                    ];
                })->toArray();

            // Prepare the response data
            $responseData = [
                'company_id' => $pendingRegistration->company_id,
                'term_id' => $pendingRegistration->term_id,
                'grade_id' => $pendingRegistration->grade_id,
                'section_name' => $pendingRegistration->section_name,
                'students' => $studentsData,
            ];

            return [
                'code' => 200,
                'data' => $responseData,
            ];
        });
    }

    public function update(PendingRegistrationStoreRequest $request, $id)
    {
        return $this->runTransaction(function () use ($request, $id) {
            $pendingRegistration = $this->pendingRegistrationRepository->find($id);
            if (!$pendingRegistration) {
                return [
                    'code' => 404,
                    'message' => 'Registro no encontrado',
                ];
            }

            $this->pendingRegistrationRepository->store([
                'id' => $id,
                'company_id' => $request->input('company_id'),
                'term_id' => $request->input('term_id'),
                'grade_id' => $request->input('grade_id'),
                'section_name' => $request->input('section_name', $pendingRegistration->section_name),
            ]);

            $studentsInput = $request->input('students', []);
            $currentStudents = $this->pendingRegistrationStudentRepository
                ->getStudentsWithSubjectsByPendingRegistrationId($id)
                ->pluck('student_id', 'id')
                ->toArray();

            $newStudentIds = array_column($studentsInput, 'student_id');

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
     
}
