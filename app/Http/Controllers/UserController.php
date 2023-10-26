<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\AssignPermissionRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Resources\User\MenuCheckBoxResource;
use App\Http\Resources\User\AssignPermission\RoleSelectResource;
use App\Http\Resources\User\UserFormResource;
use App\Http\Resources\User\UserListResource;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\MenuRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserController extends Controller
{
    private $userRepository;
    private $roleRepository;
    private $menuRepository;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository, MenuRepository $menuRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->menuRepository = $menuRepository;
    }

    public function list(Request $request)
    {
        $data = $this->userRepository->list($request->all());
        $users = UserListResource::collection($data);

        return [
            'code' => 200,
            'users' => $users,
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
            $data = $this->userRepository->find($id);
            $data = new UserFormResource($data);
        }

        return response()->json([
            "form" => $data
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $this->userRepository->store($request->except(["photo"]));

            if ($request->file('photo')) {
                $subdomain = getSubdomain($request);
                $file = $request->file('photo');
                $path = $request->root() . '/' . $subdomain . '/' . $file->store('users/user_' . $data->id . $request->input('photo'), 'public');
                $data->photo = $path;
                $data->save();
            }

            DB::commit();

            $msg = 'agregado';
            if (!empty($request['id'])) {
                $msg = 'modificado';
            }

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
            $data = $this->userRepository->find($id);
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

            $model = $this->userRepository->changeState($request->input('id'), $request->input('value'), $request->input('field'));

            ($model->state == 1) ? $msg = 'Activado' : $msg = 'Inactivado';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Usuario ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
