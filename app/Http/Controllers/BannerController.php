<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banner\BannerStoreRequest;
use App\Http\Resources\Banner\BannerFormResource;
use App\Http\Resources\Banner\BannerListResource;
use App\Repositories\BannerRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BannerController extends Controller
{

    private $bannerRepository;

    public function __construct(BannerRepository $bannerRepository)
    {
        $this->bannerRepository = $bannerRepository;
    }

    public function list(Request $request)
    {
        $data = $this->bannerRepository->list($request->all());
        $banners = BannerListResource::collection($data);

        return [
            'banners' => $banners,
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
            $data = $this->bannerRepository->find($id);
            $data = new BannerFormResource($data);
        }

        return response()->json([
            "form" => $data,
        ]);
    }

    public function store(BannerStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->bannerRepository->store($request->except("path"));

            if ($request->file('path')) {
                $file = $request->file('path');
                $path = $request->root() . '/storage/' . $file->store('/banners/banner_' . $data->id  . $request->input('path'), 'public');
                $data->path = $path;
            }
            $data->save();

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
            $data = $this->bannerRepository->find($id);
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

            $model = $this->bannerRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

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
        $data = $this->bannerRepository->list(["typeData" => "all", "state" => 1]);
        $banners = BannerListResource::collection($data);

        return [
            'banners' => $banners,
        ];
    }
}
