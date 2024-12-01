<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banner\BannerStoreRequest;
use App\Http\Resources\Banner\BannerFormResource;
use App\Http\Resources\Banner\BannerListResource;
use App\Repositories\BannerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BannerController extends Controller
{
    public function __construct(
        protected BannerRepository $bannerRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->bannerRepository->list($request->all());
            $tableData = BannerListResource::collection($data);

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

    public function store(BannerStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->bannerRepository->store($request->except('path'));

            if ($request->file('path')) {
                $file = $request->file('path');
                $path = $file->store('/banners/banner_'.$data->id.$request->input('path'), 'public');
                $data->path = $path;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Banner agregado correctamente']);
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
            $banner = $this->bannerRepository->find($id);
            $form = new BannerFormResource($banner);

            return response()->json([
                'code' => 200,
                'form' => $form,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(BannerStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->bannerRepository->store($request->except('path'));

            if ($request->file('path')) {
                $file = $request->file('path');
                $path = $file->store('/banners/banner_'.$data->id.$request->input('path'), 'public');
                $data->path = $path;
                $data->save();
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Banner modificado correctamente']);
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
            $banner = $this->bannerRepository->find($id);
            if ($banner) {
                $banner->delete();
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

            $model = $this->bannerRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Banner '.$msg.' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
