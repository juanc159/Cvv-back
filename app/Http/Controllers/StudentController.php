<?php

namespace App\Http\Controllers;

use App\Exports\StudentStatisticsExport;
use App\Helpers\Constants;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentWithdrawalRequest;
use App\Http\Resources\Student\StudentFormResource;
use App\Http\Resources\Student\StudentListResource;
use App\Models\Company;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\StudentWithdrawalRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function __construct(
        protected StudentRepository $studentRepository,
        protected TypeEducationRepository $typeEducationRepository,
        protected SectionRepository $sectionRepository,
        protected QueryController $queryController,
        protected StudentWithdrawalRepository $studentWithdrawalRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->studentRepository->list($request->all());
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

            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'typeEducations' => $typeEducations,
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

            $selectInfiniteCountries = $this->queryController->selectInfiniteCountries(new Request([
                "idsAllowed" => [Constants::COUNTRY_ID_COLOMBIA, Constants::COUNTRY_ID_VENEZUELA]
            ]));


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

            $sections = $this->sectionRepository->selectList();

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeEducations' => $typeEducations,
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
                "id" => $student->id,
                "full_name" => $student->full_name,
                "identity_document" => $student->identity_document,
                "grade_name" => $student->grade?->name,
                "section_name" => $student->section?->name,

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

            if (!$student) {
                return response()->json(['message' => 'Estudiante no encontrado'], 404);
            }

            $studentWithdrawal = $this->studentWithdrawalRepository->searchOne([
                'student_id' => $request->input('student_id')
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

    public function studentStatistics()
    {
        $companyId = 1;
        $year = 2024;
        $type_education_id = [1, 2, 3];
        $company = Company::findOrFail($companyId);
        $companyCountryId = $company->country_id;
    
        // Obtener el primer día del mes actual
        $currentMonthStart = now()->startOfMonth();
    
        // Consulta para Ingresos con tipo de educación
         $ingresos = Student::join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->where('students.company_id', $companyId)
            ->whereYear('students.created_at', $year)
            ->whereIn('students.type_education_id', $type_education_id)
            ->selectRaw('
                type_education.name as type_education_name,
                YEAR(students.created_at) as year,
                MONTH(students.created_at) as month,
                students.grade_id,
                students.section_id,
                grades.name as grade_name,
                sections.name as section_name,
                COUNT(*) as ingresos_total,
                SUM(CASE WHEN students.gender = "M" THEN 1 ELSE 0 END) as ingresos_male,
                SUM(CASE WHEN students.gender = "F" THEN 1 ELSE 0 END) as ingresos_female,
                SUM(CASE WHEN students.country_id = ? THEN 1 ELSE 0 END) as nacionales_total,
                SUM(CASE WHEN students.country_id = ? AND students.gender = "M" THEN 1 ELSE 0 END) as nacionales_male,
                SUM(CASE WHEN students.country_id = ? AND students.gender = "F" THEN 1 ELSE 0 END) as nacionales_female,
                SUM(CASE WHEN students.country_id != ? THEN 1 ELSE 0 END) as extranjeros_total,
                SUM(CASE WHEN students.country_id != ? AND students.gender = "M" THEN 1 ELSE 0 END) as extranjeros_male,
                SUM(CASE WHEN students.country_id != ? AND students.gender = "F" THEN 1 ELSE 0 END) as extranjeros_female,
                SUM(CASE WHEN students.real_entry_date < ? THEN 1 ELSE 0 END) as previos_total,
                SUM(CASE WHEN students.real_entry_date < ? AND students.gender = "M" THEN 1 ELSE 0 END) as previos_male,
                SUM(CASE WHEN students.real_entry_date < ? AND students.gender = "F" THEN 1 ELSE 0 END) as previos_female
            ', array_merge(array_fill(0, 6, $companyCountryId), [$currentMonthStart, $currentMonthStart, $currentMonthStart]))
            ->groupBy('type_education.name', 'year', 'month', 'students.grade_id', 'students.section_id', 'grades.name', 'sections.name')
            ->get();

        // Consulta para Egresos con tipo de educación
        $egresos = Student::join('student_withdrawals', 'students.id', '=', 'student_withdrawals.student_id')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->where('students.company_id', $companyId)
            ->whereYear('student_withdrawals.date', $year)
            ->whereIn('students.type_education_id', $type_education_id)
            ->selectRaw('
                type_education.name as type_education_name,
                YEAR(student_withdrawals.date) as year,
                MONTH(student_withdrawals.date) as month,
                students.grade_id,
                students.section_id,
                grades.name as grade_name,
                sections.name as section_name,
                COUNT(*) as egresos_total,
                SUM(CASE WHEN students.gender = "M" THEN 1 ELSE 0 END) as egresos_male,
                SUM(CASE WHEN students.gender = "F" THEN 1 ELSE 0 END) as egresos_female
            ')
            ->groupBy('type_education.name', 'year', 'month', 'students.grade_id', 'students.section_id', 'grades.name', 'sections.name')
            ->get();

        // Combinar resultados
        $statistics = collect();

        foreach ($ingresos as $ingreso) {
            $key = $ingreso->type_education_name . '-' . $ingreso->year . '-' . $ingreso->month . '-' . $ingreso->grade_id . '-' . $ingreso->section_id;
            $statistics->put($key, [
                'type_education_name' => $ingreso->type_education_name,
                'year' => $ingreso->year,
                'month' => $ingreso->month,
                'grade_name' => $ingreso->grade_name,
                'section_name' => $ingreso->section_name,
                'ingresos_total' => $ingreso->ingresos_total,
                'ingresos_male' => $ingreso->ingresos_male,
                'ingresos_female' => $ingreso->ingresos_female,
                'nacionales_total' => $ingreso->nacionales_total,
                'nacionales_male' => $ingreso->nacionales_male,
                'nacionales_female' => $ingreso->nacionales_female,
                'extranjeros_total' => $ingreso->extranjeros_total,
                'extranjeros_male' => $ingreso->extranjeros_male,
                'extranjeros_female' => $ingreso->extranjeros_female,
                'previos_total' => $ingreso->previos_total,
                'previos_male' => $ingreso->previos_male,
                'previos_female' => $ingreso->previos_female,
                'egresos_total' => 0,
                'egresos_male' => 0,
                'egresos_female' => 0,
            ]);
        }

        foreach ($egresos as $egreso) {
            $key = $egreso->type_education_name . '-' . $egreso->year . '-' . $egreso->month . '-' . $egreso->grade_id . '-' . $egreso->section_id;
            if ($statistics->has($key)) {
                $statistics[$key]['egresos_total'] = $egreso->egresos_total;
                $statistics[$key]['egresos_male'] = $egreso->egresos_male;
                $statistics[$key]['egresos_female'] = $egreso->egresos_female;
            } else {
                $statistics->put($key, [
                    'type_education_name' => $egreso->type_education_name,
                    'year' => $egreso->year,
                    'month' => $egreso->month,
                    'grade_name' => $egreso->grade_name,
                    'section_name' => $egreso->section_name,
                    'ingresos_total' => 0,
                    'ingresos_male' => 0,
                    'ingresos_female' => 0,
                    'nacionales_total' => 0,
                    'nacionales_male' => 0,
                    'nacionales_female' => 0,
                    'extranjeros_total' => 0,
                    'extranjeros_male' => 0,
                    'extranjeros_female' => 0,
                    'previos_total' => 0,
                    'previos_male' => 0,
                    'previos_female' => 0,
                    'egresos_total' => $egreso->egresos_total,
                    'egresos_male' => $egreso->egresos_male,
                    'egresos_female' => $egreso->egresos_female,
                ]);
            }
        }

        // Ordenar por tipo de educación, año y mes
        $statistics = $statistics->sortBy([
            ['type_education_name', 'asc'],
            ['year', 'asc'],
            ['month', 'asc']
        ])->values();


        // Nueva consulta para estudiantes retirados
        $withdrawnStudents = Student::select([
            'students.identity_document',
            'students.full_name',
            'students.birthday',
            'students.gender',
            'grades.name as grade_name',
            'sections.name as section_name',
            'student_withdrawals.date as withdrawal_date',
            'student_withdrawals.reason'
        ])
            ->join('student_withdrawals', 'students.id', '=', 'student_withdrawals.student_id')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('type_education', 'students.type_education_id', '=', 'type_education.id')
            ->where('students.company_id', $companyId)
            ->whereYear('student_withdrawals.date', $year)
            ->whereIn('students.type_education_id', $type_education_id)
            ->orderBy('student_withdrawals.date', 'desc')
            ->get();
 
        return view('Exports.Student.Statistics', compact('statistics', 'withdrawnStudents'));

    }
}
