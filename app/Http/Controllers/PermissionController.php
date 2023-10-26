<?php

namespace App\Http\Controllers;

use App\Repositories\PermissionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PermissionController extends Controller
{
    private $permissionRepository;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function list(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $this->permissionRepository->list($request->all());

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => 'Resgistros enconstrados',
                'data' => $data,
            ], 200);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code' => 500,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
}
