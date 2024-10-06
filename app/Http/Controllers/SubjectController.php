<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subject\SubjectStoreRequest;
use App\Http\Resources\Subject\SubjectFormResource;
use App\Http\Resources\Subject\SubjectListResource;
use App\Repositories\SubjectRepository;
use App\Repositories\TypeEducationRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubjectController extends Controller
{
    private $subjectRepository;

    private $typeEducationRepository;

    public function __construct(SubjectRepository $subjectRepository, TypeEducationRepository $typeEducationRepository)
    {
        $this->subjectRepository = $subjectRepository;
        $this->typeEducationRepository = $typeEducationRepository;
    }

    public function list(Request $request)
    {
        $data = $this->subjectRepository->list($request->all());
        $subjects = SubjectListResource::collection($data);

        return [
            'tableData' => $subjects,
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
            $data = $this->subjectRepository->find($id);
            $data = new SubjectFormResource($data);
        }

        $typeEducations = $this->typeEducationRepository->selectList();

        return response()->json([
            'form' => $data,
            'typeEducations' => $typeEducations,
        ]);
    }

    public function store(SubjectStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->subjectRepository->store($request->except('path'));

            $data->save();

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
            $data = $this->subjectRepository->find($id);
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

            $model = $this->subjectRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro '.$msg.' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
