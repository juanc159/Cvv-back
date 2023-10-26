<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\TenantStoreRequest;
use App\Repositories\TenantRepository;
use Exception;
use Illuminate\Http\Request;
use Throwable;

class TenantController extends Controller
{
    private $tenantRepository;

    public function __construct(TenantRepository $tenantRepository)
    {
        $this->tenantRepository = $tenantRepository;
    }

    public function store(TenantStoreRequest $request)
    {
        try {
            $data = $this->tenantRepository->store($request->all());

            mkdir(storage_path('storage_'.$request['name'].'/app/public'), 777, true);

            return response()->json([
                'code' => 200,
                'message' => 'Registrado con éxito',
                'data' => $data,
            ], 200);
        } catch (Exception $th) {
            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $data = $this->tenantRepository->list($request->all());

            return response()->json([
                'code' => 200,
                'data' => $data,
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
}
