<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grade\GradeStoreRequest;
use App\Http\Resources\Grade\GradeFormResource;
use App\Http\Resources\Grade\GradeListResource;
use App\Models\TypeEducation;
use App\Repositories\GradeRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class GradeController extends Controller
{
    public function __construct(
        protected GradeRepository $gradeRepository,
        protected TypeEducationRepository $typeEducationRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->gradeRepository->list($request->all());
            $tableData = GradeListResource::collection($data);

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

    public function create(Request $request)
    {
        try {

            $typeEducations = TypeEducation::with([
                'subjects' => function ($query) use ($request) {
                    $query->where('company_id', $request->input('company_id'));
                },
            ])->get()->map(function ($value) {
                $data = [
                    'value' => $value->id,
                    'title' => $value->name,
                    'subjects' => $value->subjects,
                ];

                return $data;
            });

            return response()->json([
                'code' => 200,
                'typeEducations' => $typeEducations,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(GradeStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->gradeRepository->store($request->except(['subjects']));

            $subjects = $request->input('subjects');
            if (count($subjects) > 0) {
                $newSubjects = [];
                foreach ($subjects as $key => $value) {
                    $newSubjects[$value] = [
                        'company_id' => $request->input('company_id'),
                    ];
                }
                $data->subjects()->sync($newSubjects);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Grado agregada correctamente']);
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

    public function edit(Request $request, $id)
    {
        try {
            $grade = $this->gradeRepository->find($id);
            $form = new GradeFormResource($grade);

            $typeEducations = TypeEducation::with([
                'subjects' => function ($query) use ($request) {
                    $query->where('company_id', $request->input('company_id'));
                },
            ])->get()->map(function ($value) {
                $data = [
                    'value' => $value->id,
                    'title' => $value->name,
                    'subjects' => $value->subjects,
                ];

                return $data;
            });

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeEducations' => $typeEducations,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(GradeStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->gradeRepository->store($request->except(['subjects']));

            $subjects = $request->input('subjects');
            if (count($subjects) > 0) {
                $newSubjects = [];
                foreach ($subjects as $key => $value) {
                    $newSubjects[$value] = [
                        'company_id' => $request->input('company_id'),
                    ];
                }
                $data->subjects()->sync($newSubjects);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Grado modificado correctamente']);
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
            $grade = $this->gradeRepository->find($id);
            if ($grade) {
                $grade->delete();
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

            $model = $this->gradeRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Grade '.$msg.' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
