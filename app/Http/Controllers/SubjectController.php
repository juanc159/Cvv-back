<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subject\SubjectStoreRequest;
use App\Http\Resources\Subject\SubjectFormResource;
use App\Http\Resources\Subject\SubjectListResource;
use App\Repositories\SubjectRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubjectController extends Controller
{
    public function __construct(
        protected SubjectRepository $subjectRepository,
        protected TypeEducationRepository $typeEducationRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->subjectRepository->list($request->all());
            $tableData = SubjectListResource::collection($data);

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
            $typeEducations = $this->typeEducationRepository->selectList();

            return response()->json([
                'code' => 200,
                'typeEducations' => $typeEducations,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(SubjectStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $this->subjectRepository->store($request->all());

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Materia agregada correctamente']);
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
            $subject = $this->subjectRepository->find($id);
            $form = new SubjectFormResource($subject);

            $typeEducations = $this->typeEducationRepository->selectList();

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeEducations' => $typeEducations,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(SubjectStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $this->subjectRepository->store($request->all());

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Materia modificada correctamente']);
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
            $subject = $this->subjectRepository->find($id);
            if ($subject) {
                $subject->delete();
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

            $model = $this->subjectRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Subject ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

}
