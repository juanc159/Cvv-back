<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherAuth\TeacherAuthLoginRequest;
use App\Models\BlockData;
use App\Repositories\MenuRepository;
use App\Repositories\UserRepository;
use App\Services\MailService;
use Illuminate\Support\Facades\Auth;

class TeacherAuthController extends Controller
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

    public function login(TeacherAuthLoginRequest $request)
    {
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        Auth::guard('teachers')->attempt($credentials);

        $user = Auth::guard('teachers')->user();

        $blockData = BlockData::where("name","BLOCK_PAYROLL_UPLOAD")->first()->is_active;


        if ($user) {
            //datos personales
            $obj['id'] = $user->id;
            $obj['full_name'] = $user->full_name;
            $obj['photo'] = $user->photo_url;

            //colegio
            $obj['company_id'] = $user->company_id;
            $obj['company'] = $user->company;
            $obj['type_user'] = "teacher";

            $obj['blockData'] = $blockData;


            return response()->json([
                'token' => $user->createToken('PassportAuth')->accessToken,
                'user' => $obj,
                'menu' => [],
                'permissions' => [],
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
