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
    private $companyRepository;
    private $companyDetailRepository;
    private $typeDetailRepository;

    public function __construct(
        CompanyRepository $companyRepository,
        CompanyDetailRepository $companyDetailRepository,
        TypeDetailRepository $typeDetailRepository,
    ) {
        $this->companyRepository = $companyRepository;
        $this->companyDetailRepository = $companyDetailRepository;
        $this->typeDetailRepository = $typeDetailRepository;
    }

    public function list(Request $request)
    {
        $data = $this->companyRepository->list($request->all());
        $companies = CompanyListResource::collection($data);

        return [
            'code' => 200,
            'companies' => $companies,
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
            $data = $this->companyRepository->find($id);
            $data = new CompanyFormResource($data);
        }

        $typeDetails = $this->typeDetailRepository->selectList();

        return response()->json([
            "form" => $data,
            "typeDetails" => $typeDetails,
        ]);
    }

    public function store(CompanyStoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $this->companyRepository->store($request->except(["arrayDetails","image_principal"]));

            if ($request->file('image_principal')) {
                $file = $request->file('image_principal');
                $image_principal = $request->root() . '/storage/' . $file->store('/banners/banner_' . $data->id  . $request->input('image_principal'), 'public');
                $data->image_principal = $image_principal;
            }
            $data->save();


            $arrayDetails = json_decode($request->input(["arrayDetails"]),1);
            if (count($arrayDetails) > 0) {
                foreach ($arrayDetails as $key => $value) {
                    if ($value["delete"] == 1) {
                        $this->companyDetailRepository->delete($value["id"]);
                    }else{
                        unset($value["delete"]);
                        $value["company_id"] = $data->id;
                        $this->companyDetailRepository->store($value);
                    }
                }
            }

            DB::commit();

            $msg = 'agregado';
            if (!empty($request['id'])) {
                $msg = 'modificado';
            }
            $data = new CompanyFormResource($data);

            return response()->json(['code' => 200, 'message' => 'Registro ' . $msg . ' correctamente', 'data' => $data]);
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

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $data = $this->companyRepository->find($id);
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

            $model = $this->companyRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Usuario ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
