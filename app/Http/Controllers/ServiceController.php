<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\ServiceStoreRequest;
use App\Http\Resources\Service\ServiceFormResource;
use App\Http\Resources\Service\ServiceListResource;
use App\Repositories\ServiceRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ServiceController extends Controller
{
    private $serviceRepository;

    public function __construct(ServiceRepository $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }

    public function list(Request $request)
    {
        $data = $this->serviceRepository->list($request->all());
        $services = ServiceListResource::collection($data);

        return [
            'tableData' => $services,
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
            $data = $this->serviceRepository->find($id);
            $data = new ServiceFormResource($data);
        }

        return response()->json([
            'form' => $data,
        ]);
    }

    public function store(ServiceStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->serviceRepository->store($request->except('image'));

            if ($request->file('image')) {
                $file = $request->file('image');
                $image = $request->root() . '/storage/' . $file->store('/services/service_' . $data->id . $request->input('image'), 'public');
                $data->image = $image;
            }
            $data->save();

            $msg = 'agregado';
            if (! empty($request['id']) && $request['id'] != "null") {
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
            $data = $this->serviceRepository->find($id);
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

            $model = $this->serviceRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function pw_list()
    {
        $data = $this->serviceRepository->list(['typeData' => 'all', 'state' => 1]);
        $services = ServiceListResource::collection($data);

        return [
            'services' => $services,
        ];
    }
}
