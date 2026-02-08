<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activity\ActivityStoreRequest;
use App\Http\Resources\Activity\ActivityFormResource;
use App\Http\Resources\Activity\ActivityListResource;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherComplementary;
use App\Repositories\ActivityRepository;
use App\Repositories\TeacherRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ActivityController extends Controller
{
    public function __construct(
        protected TeacherRepository $teacherRepository,
        protected ActivityRepository $activityRepository,
        protected QueryController $queryController,
    ) {}

    public function list(Request $request)
    {
        try {

            // IMPORTANTE: teacher_id no debe venir del front
            $payload = array_merge($request->all(), [
                'teacher_id' => $request->input('user_id'),
            ]);

            $data = $this->activityRepository->paginate($payload);
            $tableData = ActivityListResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error Al Buscar Los Datos',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }




    public function create(Request $request)
    {
        try {

            $companyId = $request->input('company_id');
            $teacherId = $request->input('teacher_id');

            // Validación mínima: la empresa debe existir en payload
            if (empty($companyId)) {
                return response()->json([
                    'code' => 422,
                    'message' => 'company_id es requerido',
                ], 422);
            }

            // Validar que el teacher logueado pertenece a esa company
            // (evita que manden company_id de otra institución)
            $teacher = Teacher::query()
                ->select(['id', 'company_id'])
                ->where('id', $teacherId)
                ->firstOrFail();

            if ((string) $teacher->company_id !== (string) $companyId) {
                return response()->json([
                    'code' => 403,
                    'message' => 'Empresa no válida para este docente',
                ], 403);
            }


            $options = $this->teacherRepository->getTeacherActivityOptions($teacher->id);


            $activityStatusEnum = $this->queryController->selectActivityStatusEnum(request());

            return response()->json([
                'code' => 200,
                ...$activityStatusEnum,
                'data' => [
                    'grades' => $options['grades'],
                    'sections' => $options['sections'],
                    'subjects' => $options['subjects'],
                    'rules' => $options['rules'],
                ],
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error al cargar datos para crear actividad',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }


    public function store(ActivityStoreRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                // 1) datos validados (lo correcto)
                $payload = $request->validated();

                // 2) guardar por repositorio
                $activity = $this->activityRepository->store($payload);

                return response()->json([
                    'code' => 200,
                    'message' => 'Actividad agregada correctamente',
                    'data' => ['id' => $activity->id],
                ]);
            });
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Algo ocurrió, comunícate con el equipo de desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $activity = $this->activityRepository->find($id);
            $form = new ActivityFormResource($activity);

            $options = $this->teacherRepository->getTeacherActivityOptions($activity->teacher_id);

            $activityStatusEnum = $this->queryController->selectActivityStatusEnum(request());


            return response()->json([
                'code' => 200,
                'form' => $form,
                'data' => [
                    'grades' => $options['grades'],
                    'sections' => $options['sections'],
                    'subjects' => $options['subjects'],
                    'rules' => $options['rules'],
                ],
                ...$activityStatusEnum,


            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(ActivityStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->activityRepository->store($request->except('path'));

            if ($request->file('path')) {
                $file = $request->file('path');
                $path = $file->store('/activitys/activity_' . $data->id . $request->input('path'), 'public');
                $data->path = $path;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Activity modificado correctamente']);
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
            $activity = $this->activityRepository->find($id);
            if ($activity) {
                $activity->delete();
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

            $model = $this->activityRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Activity ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
