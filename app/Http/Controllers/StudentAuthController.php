<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentAuth\StudentAuthLoginRequest;
use App\Repositories\MenuRepository;
use App\Repositories\UserRepository;
use App\Services\MailService;
use Illuminate\Support\Facades\Auth;

class StudentAuthController extends Controller
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

    public function login(StudentAuthLoginRequest $request)
    {
        $credentials = [
            'identity_document' => $request->input('identity_document'),
            'password' => $request->input('password'),
        ];

        Auth::guard('students')->attempt($credentials);

        $user = Auth::guard('students')->user();

        if ($user) {
            //datos personales
            $obj['id'] = $user->id;
            $obj['full_name'] = $user->full_name;
            $obj['photo'] = $user->photo_url;

            //colegio
            $obj['company_id'] = $user->company_id;
            $obj['company'] = $user->company;

            //informacion año, grado y seccion
            $obj['type_education_id'] = $user->type_education_id;
            $obj['type_education_name'] = $user->typeEducation->name;
            $obj['grade_id'] = $user->grade_id;
            $obj['grade_name'] = $user->grade->name;
            $obj['section_id'] = $user->section_id;
            $obj['section_name'] = $user->section->name;

            // Obtener las planificaciones del estudiante
            $obj['teacherPlannings'] = $user->teacherPlannings;

            $obj['pdf'] = $user->pdf;




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
