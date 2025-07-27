<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleStoreRequest;
use App\Http\Resources\Role\MenuCheckBoxResource;
use App\Http\Resources\Role\RoleFormResource;
use App\Http\Resources\Role\RoleListResource;
use App\Models\Role;
use App\Repositories\MenuRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RoleController extends Controller
{
    private $roleRepository;

    private $menuRepository;

    public function __construct(RoleRepository $roleRepository, MenuRepository $menuRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->menuRepository = $menuRepository;
    }

    public function index(Request $request)
    {
        $data = $this->roleRepository->list([
            ...['typeData' => 'all'],
            ...$request->all(),
        ]);
        $tableData = RoleListResource::collection($data);

        return [
            'code' => 200,
            'tableData' => $tableData,
        ];
    }

    public function create()
    {
        try {
            $menus = $this->menuRepository->list([
                'father_null' => true,
                'withPermissions' => true,
            ], ['children']);

            $menus = MenuCheckBoxResource::collection($menus);

            return response()->json([
                'menus' => $menus,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Hubo un error al obtener el listado de permisos, por favor comuníquese con el equipo de soporte',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $role = $this->roleRepository->find($id);

            $menus = $this->menuRepository->list([
                'typeData' => 'all',
                'father_null' => true,
                'withPermissions' => true,
            ], ['children']);

            $menus = MenuCheckBoxResource::collection($menus);

            return response()->json([
                'code' => 200,
                'role' => new RoleFormResource($role),
                'menus' => $menus,
            ]);

        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Hubo un error al obtener la información del rol, por favor comuníquese con el equipo de soporte',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function store(RoleStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $post = $request->except(['permissions']);

            do {
                $nameRole = Str::random(10); // Genera un string aleatorio de 10 caracteres
            } while (Role::where('name', $nameRole)->exists()); // Verifica si ya existe en la base de datos

            $post['name'] = $nameRole;

            $data = $this->roleRepository->store($post);

            $permissions = [
                ...$request['permissions'],
                ...[1],
            ];

            $data->permissions()->sync($permissions);
            DB::commit();

            clearCacheLaravel();

            $msg = 'agregado';
            if (! empty($request['id'])) {
                $msg = 'modificado';
            }

            return response()->json(['code' => 200, 'message' => 'Registro '.$msg.' correctamente', 'data' => $data]);
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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $data = $this->roleRepository->find($id);
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
}
