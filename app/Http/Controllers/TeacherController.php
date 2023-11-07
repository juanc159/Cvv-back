<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\TeacherStoreRequest;
use App\Http\Resources\Teacher\TeacherFormResource;
use App\Http\Resources\Teacher\TeacherListResource;
use App\Repositories\GradeRepository;
use App\Repositories\JobPositionRepository;
use App\Repositories\SectionRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
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
    private $sectionRepository;
    private $gradeRepository;
    private $teacherComplementaryRepository;

    public function __construct(
        TeacherRepository $teacherRepository,
        JobPositionRepository $jobPositionRepository,
        TypeEducationRepository $typeEducationRepository,
        SubjectRepository $subjectRepository,
        SectionRepository $sectionRepository,
        GradeRepository $gradeRepository,
        TeacherComplementaryRepository $teacherComplementaryRepository,
    ) {
        $this->teacherRepository = $teacherRepository;
        $this->jobPositionRepository = $jobPositionRepository;
        $this->typeEducationRepository = $typeEducationRepository;
        $this->subjectRepository = $subjectRepository;
        $this->sectionRepository = $sectionRepository;
        $this->gradeRepository = $gradeRepository;
        $this->teacherComplementaryRepository = $teacherComplementaryRepository;
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
        $sections = $this->sectionRepository->selectList();
        $grades = $this->gradeRepository->selectList();

        return response()->json([
            "form" => $data,
            "jobPositions" => $jobPositions,
            "typeEducations" => $typeEducations,
            "subjects" => $subjects,
            "sections" => $sections,
            "grades" => $grades,
        ]);
    }

    public function store(TeacherStoreRequest $request)
    {
        try {
            DB::beginTransaction();

           $data = $this->teacherRepository->store($request->except(["photo","complementaries"]));

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $request->root() . '/storage/' . $file->store('company_'.$data->company_id.'/teachers/teacher_' . $data->id  . $request->input('photo'), 'public');
                $data->photo = $photo;
            }

            $data->save();

             $complementaries = json_decode($request->input("complementaries"),1);
            if(count($complementaries)>0){
                foreach ($complementaries as $key => $value) {
                    if($value["delete"]==1){
                        $this->teacherComplementaryRepository->delete($value["id"]);
                    }else{
                        $subjectsArray = collect($value["subjects"])->pluck("value")->toArray();

                        $this->teacherComplementaryRepository->store([
                            "id" => $value["id"],
                            "grade_id" => $value["grade_id"],
                            "teacher_id" => $data->id,
                            "section_id" => $value["section_id"],
                            "subject_ids" => implode(', ', $subjectsArray),
                            "id" => $value["id"],
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
