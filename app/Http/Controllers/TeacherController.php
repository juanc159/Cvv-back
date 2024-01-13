<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\TeacherStoreRequest;
use App\Http\Resources\Teacher\TeacherFormResource;
use App\Http\Resources\Teacher\TeacherListResource;
use App\Http\Resources\Teacher\TeacherPlanningResource;
use App\Repositories\GradeRepository;
use App\Repositories\JobPositionRepository;
use App\Repositories\SectionRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TeacherPlanningRepository;
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

    private $teacherPlanningRepository;

    public function __construct(
        TeacherRepository $teacherRepository,
        JobPositionRepository $jobPositionRepository,
        TypeEducationRepository $typeEducationRepository,
        SubjectRepository $subjectRepository,
        SectionRepository $sectionRepository,
        GradeRepository $gradeRepository,
        TeacherComplementaryRepository $teacherComplementaryRepository,
        TeacherPlanningRepository $teacherPlanningRepository,
    ) {
        $this->teacherRepository = $teacherRepository;
        $this->jobPositionRepository = $jobPositionRepository;
        $this->typeEducationRepository = $typeEducationRepository;
        $this->subjectRepository = $subjectRepository;
        $this->sectionRepository = $sectionRepository;
        $this->gradeRepository = $gradeRepository;
        $this->teacherComplementaryRepository = $teacherComplementaryRepository;
        $this->teacherPlanningRepository = $teacherPlanningRepository;
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

    public function dataForm($action = 'create', $id = null)
    {
        $data = null;
        if ($id) {
            $data = $this->teacherRepository->find($id);
            $data = new TeacherFormResource($data);
        }

        $jobPositions = $this->jobPositionRepository->selectList();
        $typeEducations = $this->typeEducationRepository->selectList();
        $subjects = $this->subjectRepository->selectList(select: ['type_education_id']);
        $sections = $this->sectionRepository->selectList();
        $grades = $this->gradeRepository->selectList(select: ['type_education_id']);

        return response()->json([
            'form' => $data,
            'jobPositions' => $jobPositions,
            'typeEducations' => $typeEducations,
            'subjects' => $subjects,
            'sections' => $sections,
            'grades' => $grades,
        ]);
    }

    public function store(TeacherStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->teacherRepository->store($request->except(['photo', 'complementaries']));

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $photo = $request->root().'/storage/'.$file->store('company_'.$data->company_id.'/teachers/teacher_'.$data->id.$request->input('photo'), 'public');
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
            if (! empty($request['id'])) {
                $msg = 'modificado';
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro '.$msg.' correctamente', 'data' => $data]);
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

            return response()->json(['code' => 200, 'message' => 'Registro '.$msg.' con éxito']);
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
 return $request->all();
            for ($i = 0; $i < $request->input('files_cant'); $i++) {
                if ($request->input('file_delete_'.$i) == 1) {
                    return 1;
                    $this->teacherPlanningRepository->delete($request->input('file_id_'.$i));
                } else {
                    return 2;
                  return  $teacherPlanning = $this->teacherPlanningRepository->store([
                        'id' => $request->input('file_id_'.$i) === 'null' ? null : $request->input('file_id_'.$i),
                        'teacher_id' => $teacher->id,
                        'grade_id' => $request->input('file_grade_id_'.$i),
                        'section_id' => $request->input('file_section_id_'.$i),
                        'subject_id' => $request->input('file_subject_id_'.$i),
                        'path' => $request->input('file_file_'.$i),
                        'name' => $request->input('file_name_'.$i),
                    ]);

                    // if ($request->file('file_file_'.$i)) {
                    //     $file = $request->file('file_file_'.$i);
                    //     $path = $request->root().'/storage/'.$file->store('company_'.$teacher->company_id.'/teachers/teacher_'.$request->input('teacher_id').'/plannings'.$request->input('file_file_'.$i), 'public');
                    //     $teacherPlanning->path = $path;
                    // }
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
}
