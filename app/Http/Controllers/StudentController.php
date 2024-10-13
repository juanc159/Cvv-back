<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedExport;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Resources\Student\StudentFormResource;
use App\Http\Resources\Student\StudentListResource;
use App\Models\Student;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{

    public function __construct(
        private StudentRepository $studentRepository,
        private TypeEducationRepository $typeEducationRepository,
        private SectionRepository $sectionRepository,
    ) {}

    public function list(Request $request)
    {
        $data = $this->studentRepository->list($request->all());
        $students = StudentListResource::collection($data);

        return [
            'tableData' => $students,
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
            $data = $this->studentRepository->find($id);
            $data = new StudentFormResource($data);
        }

        $typeEducations = $this->typeEducationRepository->list(
            request: [
                "typeData" => "all",
            ],
            with: ["grades"]
        )->map(function ($value) {
            return [
                "value" => $value->id,
                "title" => $value->name,
                "grades" => $value->grades->map(function ($value2) {
                    return [
                        "value" => $value2->id,
                        "title" => $value2->name,
                    ];
                }),
            ];
        });

        $sections = $this->sectionRepository->selectList();

        return response()->json([
            'form' => $data,
            'typeEducations' => $typeEducations,
            'sections' => $sections,
        ]);
    }

    public function store(StudentStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['photo']);


            $data = $this->studentRepository->store($post);

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $request->root() . '/storage/' . $file->store('company_' . $data->company_id . '/student/student_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $photo;
            }

            $data->save();

            $data = new StudentFormResource($data);

            $msg = 'agregado';
            if (!empty($request['id'])) {
                $msg = 'modificado';
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' correctamente', 'data' => $data]);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage(), 'line' => $th->getLine()], 500);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $data = $this->studentRepository->find($id);
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

            $model = $this->studentRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
