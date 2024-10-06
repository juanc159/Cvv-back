<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grade\GradeStoreRequest;
use App\Http\Resources\Grade\GradeFormResource;
use App\Http\Resources\Grade\GradeListResource;
use App\Repositories\GradeRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TypeEducationRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class GradeController extends Controller
{
    private $gradeRepository;

    private $typeEducationRepository;
    private $subjectRepository;

    public function __construct(GradeRepository $gradeRepository, TypeEducationRepository $typeEducationRepository, SubjectRepository $subjectRepository)
    {
        $this->gradeRepository = $gradeRepository;
        $this->typeEducationRepository = $typeEducationRepository;
        $this->subjectRepository = $subjectRepository;
    }

    public function list(Request $request)
    {
        $data = $this->gradeRepository->list($request->all());
        $grades = GradeListResource::collection($data);

        return [
            'tableData' => $grades,
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
            $data = $this->gradeRepository->find($id);
            $data = new GradeFormResource($data);
        }

        $typeEducations = $this->typeEducationRepository->selectList(with: ["subjects"]);


        return response()->json([
            'form' => $data,
            'typeEducations' => $typeEducations,
        ]);
    }

    public function store(GradeStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->gradeRepository->store($request->except(["subjects"]));

            $data->save();


            $subjects = $request->input("subjects");
            if (count($subjects) > 0) {
                $newSubjects = [];
                foreach ($subjects as $key => $value) {
                    $newSubjects[$value] = [
                        "company_id" => $request->input("company_id"),
                    ];
                }
                $data->subjects()->sync($newSubjects);
            }


            $data = new GradeFormResource($data);


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
            $data = $this->gradeRepository->find($id);
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

            $model = $this->gradeRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
