<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\ServiceStoreRequest;
use App\Http\Resources\Service\ServiceFormResource;
use App\Http\Resources\Service\ServiceListResource;
use App\Repositories\ServiceRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ServiceController extends Controller
{
    public function __construct(
        protected ServiceRepository $serviceRepository,
        protected TypeEducationRepository $typeEducationRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->serviceRepository->paginate($request->all());
            $tableData = ServiceListResource::collection($data);

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

            return response()->json([
                'code' => 200,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(ServiceStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->serviceRepository->store($request->except('image'));

            if ($request->file('image')) {
                $file = $request->file('image');
                $image = $file->store('/services/service_'.$data->id.$request->input('image'), 'public');
                $data->image = $image;
                $data->save();
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

    public function edit($id)
    {
        try {
            $service = $this->serviceRepository->find($id);
            $form = new ServiceFormResource($service);

            return response()->json([
                'code' => 200,
                'form' => $form,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(ServiceStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->serviceRepository->store($request->except('image'));

            if ($request->file('image')) {
                $file = $request->file('image');
                $image = $file->store('/services/service_'.$data->id.$request->input('image'), 'public');
                $data->image = $image;
                $data->save();
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
            $service = $this->serviceRepository->find($id);
            if ($service) {
                $service->delete();
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

            $model = $this->serviceRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Service '.$msg.' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
