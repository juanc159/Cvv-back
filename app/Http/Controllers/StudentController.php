<?php

namespace App\Http\Controllers;

use App\Exports\StudentStatisticsExport;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentWithdrawalRequest;
use App\Http\Resources\Student\StudentFormResource;
use App\Http\Resources\Student\StudentListResource;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\StudentWithdrawalRepository;
use App\Repositories\TypeDocumentRepository;
use App\Repositories\TypeEducationRepository;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        protected StudentRepository $studentRepository,
        protected TypeEducationRepository $typeEducationRepository,
        protected SectionRepository $sectionRepository,
        protected QueryController $queryController,
        protected StudentWithdrawalRepository $studentWithdrawalRepository,
        protected CacheService $cacheService,
        protected TypeDocumentRepository $typeDocumentRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->studentRepository->paginate($request->all());
            $tableData = StudentListResource::collection($data);

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

    public function create()
    {
        try {

            $selectInfiniteCountries = $this->queryController->selectInfiniteCountries(request());

            $typeEducations = $this->typeEducationRepository->list(
                request: [
                    'typeData' => 'all',
                ],
                with: ['grades']
            )->map(function ($value) {
                return [
                    'value' => $value->id,
                    'title' => $value->name,
                    'grades' => $value->grades->map(function ($value2) {
                        return [
                            'value' => $value2->id,
                            'title' => $value2->name,
                        ];
                    }),
                ];
            });

            $typeDocuments = $this->typeDocumentRepository->selectList();

            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'typeEducations' => $typeEducations,
                'typeDocuments' => $typeDocuments,
                'sections' => $sections,
                ...$selectInfiniteCountries,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(StudentStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo']);

            $data = $this->studentRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $file->store('company_' . $data->company_id . '/student/student_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $photo;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Estudiante agregado correctamente']);
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

    public function edit($id)
    {
        try {

            $selectInfiniteCountries = $this->queryController->selectInfiniteCountries(request());

            $student = $this->studentRepository->find($id);
            $form = new StudentFormResource($student);

            $typeEducations = $this->typeEducationRepository->list(
                request: [
                    'typeData' => 'all',
                ],
                with: ['grades']
            )->map(function ($value) {
                return [
                    'value' => $value->id,
                    'title' => $value->name,
                    'grades' => $value->grades->map(function ($value2) {
                        return [
                            'value' => $value2->id,
                            'title' => $value2->name,
                        ];
                    }),
                ];
            });

            $typeDocuments = $this->typeDocumentRepository->selectList();

            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeEducations' => $typeEducations,
                'typeDocuments' => $typeDocuments,
                'sections' => $sections,
                ...$selectInfiniteCountries,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(StudentStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo']);

            $data = $this->studentRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $file->store('company_' . $data->company_id . '/student/student_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $photo;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Estudiante modificado correctamente']);
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
            $student = $this->studentRepository->find($id);
            if ($student) {
                $student->delete();
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

            $model = $this->studentRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Student ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function resetPassword($id)
    {
        try {
            DB::beginTransaction();

            // Buscar al usuario por ID
            $model = $this->studentRepository->find($id);
            if (! $model) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // Actualizar la contraseña
            $model->password = Hash::make($model->identity_document);
            $model->save();

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Contraseña reinicida con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $student = $this->studentRepository->find($id);

            $student = [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'identity_document' => $student->identity_document,
                'grade_name' => $student->grade?->name,
                'section_name' => $student->section?->name,

            ];

            return response()->json([
                'code' => 200,
                'student' => $student,
            ]);
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function withdraw(StudentWithdrawalRequest $request)
    {
        try {
            DB::beginTransaction();

            // Check if student exists
            $student = $this->studentRepository->find($request->input('student_id'));

            if (! $student) {
                return response()->json(['message' => 'Estudiante no encontrado'], 404);
            }

            $studentWithdrawal = $this->studentWithdrawalRepository->searchOne([
                'student_id' => $request->input('student_id'),
            ]);
            if ($studentWithdrawal) {
                return response()->json(['message' => 'Estudiante ya ha sido de baja'], 404);
            }

            // Save withdrawal record
            $studentWithdrawal = $this->studentWithdrawalRepository->store([
                'student_id' => $request->input('student_id'),
                'date' => $request->input('date'),
                'reason' => $request->input('reason'),
            ]);

            $this->cacheService->clearByPrefix("string:students_statisticsData*");
            $this->cacheService->clearByPrefix("string:students_paginate*");

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => 'Baja del estudiante registrada correctamente',
            ], 200);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function studentStatistics(Request $request)
    {
        $data['dateInitial'] = $request->input('dateInitial');
        $data['dateEnd'] = $request->input('dateEnd');

        if (empty($data['dateInitial'])) {
            $data['dateInitial'] = Carbon::now()->startOfMonth()->toDateString();
            $request["dateInitial"] = $data['dateInitial'];
        }
        if (empty($data['dateEnd'])) {
            $data['dateEnd'] = Carbon::now()->endOfMonth()->toDateString();
            $request["dateEnd"] = $data['dateEnd'];
        }


        $data = $this->studentRepository->studentStatisticsData($request->all());

        // return view('Exports.Student.Statistics', compact('statistics'));

        return response()->json([
            'code' => 200,
            ...$data,
        ]);
    }

    public function statisticsExcelExport(Request $request)
    {
        $data['dateInitial'] = $request->input('dateInitial');
        $data['dateEnd'] = $request->input('dateEnd');

        if (empty($data['dateInitial'])) {
            $data['dateInitial'] = Carbon::now()->startOfMonth()->toDateString();
            $request["dateInitial"] = $data['dateInitial'];
        }
        if (empty($data['dateEnd'])) {
            $data['dateEnd'] = Carbon::now()->endOfMonth()->toDateString();
            $request["dateEnd"] = $data['dateEnd'];
        }

        $data = $this->studentRepository->studentStatisticsData($request->all());

        $excel = Excel::raw(new StudentStatisticsExport($data), \Maatwebsite\Excel\Excel::XLSX);

        $excelBase64 = base64_encode($excel);

        return response()->json(['code' => 200, 'excel' => $excelBase64]);

        // return view('Exports.Student.Statistics', compact('statistics'));
    }
}
