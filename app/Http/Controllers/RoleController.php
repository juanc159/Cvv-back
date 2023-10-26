<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\RoleStoreRequest;
use App\Http\Resources\Roles\MenuCheckBoxResource;
use App\Http\Resources\Roles\RoleFormResource;
use App\Http\Resources\Roles\RoleListResource;
use App\Repositories\MenuRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    private $roleRepository;

    private $menuRepository;

    public function __construct(RoleRepository $roleRepository, MenuRepository $menuRepository)
    {
        $this->roleRepository = $roleRepository;

        $this->menuRepository = $menuRepository;

    }

    public function dataForm()
    {
        try {
            $menus = $this->menuRepository->list([
                'withPermissions' => true,
            ]);

            $menus = MenuCheckBoxResource::collection($menus);

            return response()->json([
                'code' => 200,
                'message' => 'Datos Encontrados',
                'menus' => $menus,
            ], 200);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function store(RoleStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->all();
            unset($post['permissions']);
            $data = $this->roleRepository->store($post);

            $data->permissions()->sync($request['permissions']);

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Guardado con exito', 'data' => $data]);

        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json(['code' => 500, 'message' => 'Error Al Guardar', $th->getMessage(), $th->getLine()]);
        }
    }

    public function list(Request $request)
    {
        try {
            $roles = $this->roleRepository->list($request->all());

            $listRoles = RoleListResource::collection($roles);

            return response()->json([
                'code' => 200,
                'message' => 'Datos Encontrados',
                'listRoles' => $listRoles,
                'lastPage' => $roles->lastPage(),
                'totalData' => $roles->total(),
                'totalPage' => $roles->perPage(),
                'currentPage' => $roles->currentPage(),
            ], 200);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function info($id)
    {
        try {
            $data = $this->roleRepository->find($id);
            $data = new RoleFormResource($data);
            if ($data) {
                $code = 200;
                $message = 'Registro Encontrado';
            } else {
                $code = 404;
                $message = 'Registro No Encontrado';
            }

            return response()->json(['code' => $code, 'message' => $message, 'role' => $data]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $data = $this->roleRepository->delete($id);
            if ($data) {
                $code = 200;
                $message = 'Registro Eliminado';
            } else {
                $code = 404;
                $message = 'Registro No Encontrado';
            }
            DB::commit();

            return response()->json(['code' => $code, 'message' => $message]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }
}
