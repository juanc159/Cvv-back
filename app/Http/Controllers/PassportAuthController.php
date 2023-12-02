<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authentication\PassportAuthLoginRequest;
use App\Repositories\MenuRepository;
use App\Repositories\UserRepository;
use App\Services\MailService;
use Illuminate\Support\Facades\Auth;

class PassportAuthController extends Controller
{
    private $userRepository;

    private $menuRepository;

    private $mailService;

    public function __construct(UserRepository $userRepository, MenuRepository $menuRepository, MailService $mailService)
    {
        $this->userRepository = $userRepository;
        $this->menuRepository = $menuRepository;
        $this->mailService = $mailService;
    }

    public function login(PassportAuthLoginRequest $request)
    {
        $data = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        if (! empty($request->input('tokenGoogle'))) {
            unset($data['password']);
            // Obtener el usuario por su dirección de correo electrónico
            $user = $this->userRepository->findByEmail($data['email']);
            if ($user) {
                // Autenticar al usuario sin contraseña
                Auth::login($user);
            }
        } else {
            Auth::attempt($data);
        }

        $user = Auth::user();

        if ($user) {
            $obj['id'] = $user->id;
            $obj['name'] = $user->name;
            $obj['email'] = $user->email;
            $obj['company_id'] = $user->company_id;
            $obj['company'] = $user->company;

            if (count($user->all_permissions) > 0) {
                $menu = $this->menuRepository->list([
                    'typeData' => 'todos',
                    'father_null' => 1,
                    'permissions' => $user->all_permissions->pluck('name'),
                ], ['children.children']);
                foreach ($menu as $key => $value) {
                    $arrayMenu[$key]['title'] = $value->title;
                    $arrayMenu[$key]['to']['name'] = $value->to;
                    $arrayMenu[$key]['icon']['icon'] = $value->icon ?? 'mdi-arrow-right-thin-circle-outline';

                    if (! empty($value['children'])) {
                        foreach ($value['children'] as $key2 => $value2) {
                            $arrayMenu[$key]['children'][$key2]['title'] = $value2->title;
                            $arrayMenu[$key]['children'][$key2]['to'] = $value2->to;
                            // $arrayMenu[$key]["children"][$key2]["icon"]["icon"] = $value2->icon ?? "mdi-arrow-right-thin-circle-outline";
                            if (! empty($value2['children'])) {
                                foreach ($value2['children'] as $key3 => $value3) {
                                    $arrayMenu[$key]['children'][$key2]['children'][$key3]['title'] = $value3->title;
                                    $arrayMenu[$key]['children'][$key2]['children'][$key3]['to'] = $value3->to;
                                    // $arrayMenu[$key]["children"][$key2]["icon"]["icon"] = $value2->icon ?? "mdi-arrow-right-thin-circle-outline";
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                'token' => $user->createToken('PassportAuth')->accessToken,
                'user' => $obj,
                'permissions' => $user->all_permissions->pluck('name'),
                'menu' => $arrayMenu ?? [],
                'message' => 'Bienvenido',
                'code' => '200',
            ], 200);
        } else {
            return response()->json([
                'code' => '401',
                'error' => 'Not authorized',
                'message' => 'Credenciales incorrectas',
            ], 401);
        }
    }

    public function userInfo()
    {
        $user = Auth::user();

        return response()->json(['user' => $user], 200);
    }
}
