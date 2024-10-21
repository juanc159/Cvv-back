<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobPosition\JobPositionStoreRequest;
use App\Http\Resources\JobPosition\JobPositionFormResource;
use App\Http\Resources\JobPosition\JobPositionListResource;
use App\Repositories\JobPositionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class JobPositionController extends Controller
{

    public function __construct(
        private JobPositionRepository $jobPositionRepository,
    ) {}

    public function list(Request $request)
    {
        $data = $this->jobPositionRepository->list($request->all());
        $jobPositions = JobPositionListResource::collection($data);

        return [
            'tableData' => $jobPositions,
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
            $data = $this->jobPositionRepository->find($id);
            $data = new JobPositionFormResource($data);
        }

        return response()->json([
            'form' => $data,
        ]);
    }

    public function store(JobPositionStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->all();

            $data = $this->jobPositionRepository->store($post);

            $data = new JobPositionFormResource($data);

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
            $data = $this->jobPositionRepository->find($id);
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

            $model = $this->jobPositionRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
