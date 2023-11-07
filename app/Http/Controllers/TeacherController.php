<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\TeacherStoreRequest;
use App\Http\Resources\Teacher\TeacherFormResource;
use App\Http\Resources\Teacher\TeacherListResource;
use App\Repositories\JobPositionRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TypeEducationRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TeacherController extends Controller
{

    private $teacherRepository;
    private $jobPositionRepository;
    private $typeEducationRepository;
    private $subjectRepository;

    public function __construct(
        TeacherRepository $teacherRepository,
        JobPositionRepository $jobPositionRepository,
        TypeEducationRepository $typeEducationRepository,
        SubjectRepository $subjectRepository,
    ) {
        $this->teacherRepository = $teacherRepository;
        $this->jobPositionRepository = $jobPositionRepository;
        $this->typeEducationRepository = $typeEducationRepository;
        $this->subjectRepository = $subjectRepository;
    }

    public function list(Request $request)
    {
        $data = $this->teacherRepository->list($request->all());
        $teachers = TeacherListResource::collection($data);

        return [
            'teachers' => $teachers,
            'lastPage' => $data->lastPage(),
            'totalData' => $data->total(),
            'totalPage' => $data->perPage(),
            'currentPage' => $data->currentPage(),
        ];
    }

    public function dataForm($action = "create", $id = null)
    {
        $data = null;
        if ($id) {
            $data = $this->teacherRepository->find($id);
            $data = new TeacherFormResource($data);
        }

        $jobPositions = $this->jobPositionRepository->selectList();
        $typeEducations = $this->typeEducationRepository->selectList();
        $subjects = $this->subjectRepository->selectList();

        return response()->json([
            "form" => $data,
            "jobPositions" => $jobPositions,
            "typeEducations" => $typeEducations,
            "subjects" => $subjects,
        ]);
    }

    public function store(TeacherStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->teacherRepository->store($request->except("photo","subjects"));

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $request->root() . '/storage/' . $file->store('company_'.$data->company_id.'/teachers/teacher_' . $data->id  . $request->input('photo'), 'public');
                $data->photo = $photo;
            }

            $data->save();

            $subjects = $request->input("subjects");
            $subjects = explode(",",$subjects);
            if(count($subjects)>0){
                $data->subjects()->sync($subjects);
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
                'message' => "Algo Ocurrio, Comunicate Con El Equipo De Desarrollo",
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
}
