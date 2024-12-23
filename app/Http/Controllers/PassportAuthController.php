<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authentication\PassportAuthLoginRequest;
use App\Models\Role;
use App\Repositories\BlockDataRepository;
use App\Repositories\MenuRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PassportAuthController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected TeacherRepository $teacherRepository,
        protected StudentRepository $studentRepository,
        protected MenuRepository $menuRepository,
        protected BlockDataRepository $blockDataRepository,
        protected MailService $mailService
    ) {}

    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $this->userRepository->store($request->all(), withCompany: false);

            $role = Role::find($user->role_id);
            if ($role) {
                $user->syncRoles($role);
            }

            $accessToken = $user->createToken('authToken')->accessToken;

            DB::commit();

            return response(['user' => $user, 'access_token' => $accessToken], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['code' => 500, 'message' => $th->getMessage()], 500);
        }
    }

    public function login(PassportAuthLoginRequest $request)
    {
        try {
            $data = [
                'user' => $request->input('user'),
                'password' => $request->input('password'),
            ];

            $user = $data['user'];
            $password = $data['password'];
            $type = null;

            // Verificamos si el usuario es un correo electrónico
            if (filter_var($user, FILTER_VALIDATE_EMAIL)) {
                // Intentamos autenticarnos con el guardia de 'users' (para usuarios y profesores con email)
                $userRecord = $this->userRepository->searchUser($data);
                if (! $userRecord) {
                    // Si no se encuentra en 'users', verificamos en 'teachers' (profesores con email)
                    $userRecord = $this->teacherRepository->searchUser($data);
                    $type = 'teacher';
                } else {
                    $type = 'admin';
                }
            } else {
                // Si no es correo electrónico, asumimos que es una cédula (para estudiantes)
                $userRecord = $this->studentRepository->searchUser($data);
                $type = 'student';
            }

            if ($userRecord && Hash::check($password, $userRecord->password)) {
                // Autenticación exitosa para 'students'
                $token = $userRecord->createToken('authToken');
            } else {
                return response()->json([
                    'code' => '401',
                    'error' => 'Not authorized',
                    'message' => 'Credenciales incorrectas',
                ], 401);
            }

            if ($token && $type) {
                switch ($type) {
                    case 'admin':
                        $response = $this->loginAdmin($userRecord, $token);
                        break;
                    case 'teacher':
                        $response = $this->loginTeacher($userRecord, $token);
                        break;
                    case 'student':
                        $response = $this->loginStudent($userRecord, $token);
                        break;

                    default:
                        // code...
                        break;
                }
            }

            return response()->json($response, 200);
        } catch (Throwable $th) {
            return response()->json([
                'code' => '401',
                'error' => 'Not authorized',
                'message' => 'Credenciales incorrectas',
                $th->getMessage(),
            ], 401);
        }
    }

    public function loginAdmin($user, $token)
    {
        if ($user->company) {
            if (! $user->company?->is_active) {
                return [
                    'code' => '401',
                    'error' => 'Not authorized',
                    'message' => 'La empresa a la cual usted pertenece se encuentra inactiva',
                ];
            }
            if (! $user->is_active) {
                return [
                    'code' => '401',
                    'error' => 'Not authorized',
                    'message' => 'El usuario se encuentra inactivo',
                ];
            }
            if (! empty($user->company->final_date)) {
                $now = Carbon::now()->format('Y-m-d');
                $compareDate = Carbon::parse($user->company->final_date)->format('Y-m-d');
                if ($now >= $compareDate) {
                    return [
                        'code' => '401',
                        'error' => 'Not authorized',
                        'message' => 'La suscripción de la empresa a la cual usted pertenece, ha caducado',
                    ];
                }
            }
        }

        $obj['id'] = $user->id;
        $obj['full_name'] = $user->full_name;
        $obj['name'] = $user->name;
        $obj['surname'] = $user->surname;
        $obj['email'] = $user->email;
        $obj['rol_name'] = $user->role?->description;
        $obj['role_id'] = $user->role_id;
        $obj['company_id'] = $user->company_id;
        $obj['type_user'] = 'admin';

        $company = $user->company;

        $photo = null;
        if ($user->company?->logo && Storage::disk('public')->exists($user->company->logo)) {
            $photo = $user->company->logo;
        }

        $company['logo'] = $photo;
        $permisos = $user->getAllPermissions();
        if (count($permisos) > 0) {
            $menu = $this->menuRepository->list([
                'typeData' => 'all',
                'father_null' => 1,
                'permissions' => $permisos->pluck('name'),
            ], [
                'children' => function ($query) use ($permisos) {
                    $query->whereHas('permissions', function ($x) use ($permisos) {
                        $x->whereIn('name', $permisos->pluck('name'));
                    });
                },
                'children.children',
            ]);

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
                                if (in_array($value3->requiredPermission, $permisos->pluck('name')->toArray())) {

                                    $arrayMenu[$key]['children'][$key2]['children'][$key3]['title'] = $value3->title;
                                    $arrayMenu[$key]['children'][$key2]['children'][$key3]['to'] = $value3->to;
                                    // $arrayMenu[$key]["children"][$key2]["icon"]["icon"] = $value2->icon ?? "mdi-arrow-right-thin-circle-outline";
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'access_token' => $token->accessToken,
            'expires_at' => Carbon::parse($token->token->expires_at)->toDateTimeString(),
            'user' => $obj,
            'company' => $company,
            'permissions' => $permisos->pluck('name'),
            'menu' => $arrayMenu ?? [],
            'message' => 'Bienvenido',
            'code' => '200',
        ];
    }

    public function loginTeacher($user, $token)
    {
        //datos personales
        $obj['id'] = $user->id;
        $obj['full_name'] = $user->full_name;
        $obj['photo'] = $user->photo_url;

        //colegio
        $obj['company_id'] = $user->company_id;
        $obj['company'] = $user->company;
        $obj['type_user'] = 'teacher';

        $obj['blockData'] = false;
        $blockData = $this->blockDataRepository->searchByName("BLOCK_PAYROLL_UPLOAD");
        if ($blockData) {
            $obj['blockData'] = $blockData->is_active;
        }

        $company = $user->company;

        return [
            'token' => $token->accessToken,
            'user' => $obj,
            'company' => $company,
            'menu' => [],
            'permissions' => [],
            'message' => 'Bienvenido',
            'code' => '200',
        ];
    }

    public function loginStudent($user, $token)
    {
        if ($user) {
            //datos personales
            $obj['id'] = $user->id;
            $obj['full_name'] = $user->full_name;
            $obj['photo'] = $user->photo_url;
            $obj['identity_document'] = $user->identity_document;

            //colegio
            $obj['company_id'] = $user->company_id;
            $obj['company'] = $user->company;

            //informacion año, grado y seccion
            $obj['type_education_id'] = $user->type_education_id;
            $obj['type_education_name'] = $user->typeEducation?->name;
            $obj['grade_id'] = $user->grade_id;
            $obj['grade_name'] = $user->grade?->name;
            $obj['section_id'] = $user->section_id;
            $obj['section_name'] = $user->section?->name;

            // Obtener las planificaciones del estudiante
            $obj['teacherPlannings'] = $user->teacherPlannings;

            $obj['first_time'] = $user->first_time;
            $obj['pdf'] = $user->pdf;
            $obj['boletin'] = $user->boletin;
            $obj['type_user'] = 'student';

            $company = $user->company;

            return [
                'token' => $token->accessToken,
                'user' => $obj,
                'company' => $company,
                'menu' => [],
                'permissions' => [],
                'message' => 'Bienvenido',
                'code' => '200',
            ];
        }
    }

    public function userInfo()
    {
        $user = Auth::user();

        return response()->json(['user' => $user], 200);
    }
}
