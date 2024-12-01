<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Resources\Company\CompanyFormResource;
use App\Http\Resources\Company\CompanyListResource;
use App\Repositories\CompanyDetailRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\TypeDetailRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected TypeDetailRepository $typeDetailRepository,
        protected CompanyDetailRepository $companyDetailRepository,
    ) {}

    public function list(Request $request)
    {
        try {
            $data = $this->companyRepository->list($request->all());
            $tableData = CompanyListResource::collection($data);

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

            $typeDetails = $this->typeDetailRepository->selectList();

            return response()->json([
                'code' => 200,
                'typeDetails' => $typeDetails,
            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(CompanyStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->companyRepository->store($request->except(['arrayDetails', 'image_principal']));

            if ($request->file('image_principal')) {
                $file = $request->file('image_principal');
                $image_principal = $file->store('/banners/banner_'.$data->id.$request->input('image_principal'), 'public');
                $data->image_principal = $image_principal;
                $data->save();
            }

            $arrayDetails = json_decode($request->input(['arrayDetails']), 1);

            foreach ($arrayDetails as $value) {
                $this->companyDetailRepository->store([
                    'company_id' => $data->id,
                    'type_detail_id' => $value['type_detail_id'],
                    'icon' => $value['icon'],
                    'color' => $value['color'],
                    'content' => $value['content'],
                ]);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Compañia agregada correctamente']);
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

            $company = $this->companyRepository->find($id);
            $form = new CompanyFormResource($company);

            $typeDetails = $this->typeDetailRepository->selectList();

            return response()->json([
                'code' => 200,
                'form' => $form,
                'typeDetails' => $typeDetails,

            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(CompanyStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->companyRepository->store($request->except(['arrayDetails', 'image_principal']), $id);

            if ($request->file('image_principal')) {
                $file = $request->file('image_principal');
                $image_principal = $file->store('/banners/banner_'.$data->id.$request->input('image_principal'), 'public');
                $data->image_principal = $image_principal;
                $data->save();
            }

            $arrayDetails = json_decode($request->input(['arrayDetails']), 1);
            $arrayIds = collect($arrayDetails)->pluck('id');
            $this->companyDetailRepository->deleteArray($arrayIds, $id);

            foreach ($arrayDetails as $value) {
                $this->companyDetailRepository->store([
                    'id' => $value['id'],
                    'company_id' => $id,
                    'type_detail_id' => $value['type_detail_id'],
                    'icon' => $value['icon'],
                    'color' => $value['color'],
                    'content' => $value['content'],
                ]);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Compañia modificada correctamente']);
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
            $company = $this->companyRepository->find($id);
            if ($company) {

                // Verificar si hay registros relacionados
                if (
                    $company->users()->exists()
                ) {
                    throw new \Exception('No se puede eliminar la compañía, por que tiene relación de datos en otros módulos');
                }

                $company->delete();
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

            $model = $this->companyRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Compañia '.$msg.' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
