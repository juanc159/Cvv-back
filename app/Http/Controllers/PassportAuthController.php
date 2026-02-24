<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authentication\PassportAuthLoginRequest;
use App\Http\Requests\Authentication\PassportAuthSendResetLinkRequest;
use App\Jobs\BrevoProcessSendEmail;
use App\Models\Role;
use App\Models\User;
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
use Illuminate\Support\Facades\Password;
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
            $loginField = $request->input('user'); // Puede ser email o cédula
            $password = $request->input('password');

            // 1. BÚSQUEDA INTELIGENTE EN TABLA UNIFICADA (USERS)
            // Buscamos a alguien que tenga ese Email O esa Cédula
            $user = User::where('email', $loginField)
                ->orWhere('identity_document', $loginField)
                ->first();

            // 2. VERIFICACIÓN DE PASSWORD
            if (!$user || !Hash::check($password, $user->password)) {
                return response()->json([
                    'code' => '401',
                    'error' => 'Not authorized',
                    'message' => 'Credenciales incorrectas',
                ], 401);
            }

            // 3. VERIFICACIÓN DE ESTADO (IS_ACTIVE)
            if (!$user->is_active) {
                return response()->json([
                    'code' => '401',
                    'message' => 'El usuario se encuentra inactivo. Contacte administración.',
                ], 401);
            }

            // 4. GENERACIÓN DEL TOKEN
            // Aquí ya estamos seguros de quién es. Creamos el token.
            // Opcional: Podríamos agregar Scopes aquí: $user->createToken('authToken', [$user->type_user]);
            $token = $user->createToken('authToken');

            // 5. REDIRECCIONAMIENTO DE RESPUESTA (Strategy Pattern)
            // Dependiendo del tipo de usuario, llamamos a la función que arma la respuesta correcta
            // Nota: Pasamos $user->student o $user->teacher porque tus funciones viejas esperan el modelo del perfil

            switch ($user->type_user) {
                case 'admin':
                    return response()->json($this->loginAdmin($user, $token), 200);

                case 'teacher':
                    // Cargamos la relación del perfil de profesor
                    $teacherProfile = $user->teacher; // Asegúrate de tener la relación en el modelo User

                    if (!$teacherProfile) {
                        return response()->json(['code' => 500, 'message' => 'Perfil de docente no encontrado.'], 500);
                    }
                    // Tus funciones viejas esperan el objeto Teacher, no el User global, así que se lo pasamos
                    return response()->json($this->loginTeacher($teacherProfile, $token), 200);

                case 'student':
                    // Cargamos la relación del perfil de estudiante
                    $studentProfile = $user->student; // Asegúrate de tener la relación en el modelo User

                    if (!$studentProfile) {
                        return response()->json(['code' => 500, 'message' => 'Perfil de estudiante no encontrado.'], 500);
                    }
                    return response()->json($this->loginStudent($studentProfile, $token), 200);

                default:
                    return response()->json(['code' => 403, 'message' => 'Tipo de usuario no válido'], 403);
            }
        } catch (Throwable $th) {
            return response()->json([
                'code' => '500',
                'error' => 'Internal Server Error',
                'message' => 'Ocurrió un error en el servidor',
                'details' => $th->getMessage() // Solo para desarrollo
            ], 500);
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
        // datos personales
        $obj['id'] = $user->id;
        $obj['full_name'] = $user->full_name;
        $obj['photo'] = $user->photo_url;

        // colegio
        $obj['company_id'] = $user->company_id;
        $obj['company'] = $user->company;
        $obj['type_user'] = 'teacher';

        $obj['blockData'] = false;
        $blockData = $this->blockDataRepository->searchByName('BLOCK_PAYROLL_UPLOAD');
        if ($blockData) {
            $obj['blockData'] = $blockData->is_active;
        }

        $company = $user->company;

        // --------------------------------------------------
        // 2. CONSTRUCCIÓN DEL MENÚ (LÓGICA AGREGADA)
        // --------------------------------------------------

        // A. Definimos los items "crudos" que ve el estudiante
        $rawMenu = [
            [
                'title' => 'Inicio',
                'to' => 'DashboardTeacher', // Debe coincidir con el 'name' en tu archivo Vue
                'icon' => 'tabler-home',
                // 'children' => [] // Si tuviera hijos se ponen aquí
            ],
            [
                'title' => 'Actividades',
                'to' => 'ActivitiesTeacher-List', // Nombre de la ruta que crearemos ahora
                'icon' => 'tabler-notebook', // Un icono acorde
            ],
            // [
            //     'title' => 'Mis Actividades',
            //     'to' => 'ActivitiesStudent', // Nombre de la ruta Vue
            //     'icon' => 'tabler-backpack', // Un icono de mochila queda bien
            // ],
            // Aquí puedes agregar más items en el futuro (ej: 'Mis Notas', 'Horario')
        ];

        $arrayMenu = [];

        // B. Transformamos al formato que exige tu Frontend (con to.name e icon.icon)
        foreach ($rawMenu as $key => $value) {
            $arrayMenu[$key]['title'] = $value['title'];

            // Formateo de ruta
            $arrayMenu[$key]['to']['name'] = $value['to'];

            // Formateo de icono
            $arrayMenu[$key]['icon']['icon'] = $value['icon'] ?? 'mdi-circle-outline';

            // Lógica para hijos (Children) si existieran
            if (!empty($value['children'])) {
                foreach ($value['children'] as $key2 => $value2) {
                    $arrayMenu[$key]['children'][$key2]['title'] = $value2['title'];
                    $arrayMenu[$key]['children'][$key2]['to']['name'] = $value2['to'];
                    // Puedes agregar lógica de iconos para hijos aquí si la necesitas
                }
            }
        }

        return [
            'access_token' => $token->accessToken,
            'user' => $obj,
            'company' => $company,
            'menu' => $arrayMenu,
            'permissions' => [],
            'message' => 'Bienvenido',
            'code' => '200',
        ];
    }

    public function loginStudent($user, $token)
    {
        if ($user) {
            // datos personales
            $obj['id'] = $user->id;
            $obj['full_name'] = $user->full_name;
            $obj['photo'] = $user->photo_url;
            $obj['identity_document'] = $user->identity_document;

            // colegio
            $obj['company_id'] = $user->company_id;
            $obj['company'] = $user->company;

            // informacion año, grado y seccion
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
            $obj['solvencyCertificate'] = $user->solvencyCertificate;
            $obj['boletin'] = $user->boletin;
            $obj['type_user'] = 'student';
            $obj['url_to_download_prosecucion_pdf'] = null;

            // si es de Educación Inicial
            if ($user->type_education_id == 1) {
                // solo Primer Nivel y Segundo Nivel
                if ($user->grade_id == 1 || $user->grade_id == 2) {
                    // $obj['url_to_download_prosecucion_pdf'] = '/documentStudent/prosecutionInitialEducation?grade_id=' . urlencode($user->grade_id) . '&section_id=' . urlencode($user->section_id) . '&ordering=full_name&company_id=' . urlencode($user->company_id) . '&student_id=' . urlencode($user->id);
                }
                // solo si es Tercer Nivel
                if ($user->grade_id == 3) {
                    // $obj['url_to_download_prosecucion_pdf'] = '/documentStudent/certificateInitialEducation?section_id=' . urlencode($user->section_id) . '&ordering=full_name&company_id=' . urlencode($user->company_id) . '&student_id=' . urlencode($user->id);
                }
            }

            // si es de Educación Primaria
            if ($user->type_education_id == 2) {
                // $obj['url_to_download_prosecucion_pdf'] = '/documentStudent/prosecutionPrimaryEducation?grade_id=' . urlencode($user->grade_id) . '&section_id=' . urlencode($user->section_id) . '&ordering=full_name&company_id=' . urlencode($user->company_id) . '&student_id=' . urlencode($user->id);
            }

            $company = $user->company;

            // --------------------------------------------------
            // 2. CONSTRUCCIÓN DEL MENÚ (LÓGICA AGREGADA)
            // --------------------------------------------------

            // A. Definimos los items "crudos" que ve el estudiante
            $rawMenu = [
                [
                    'title' => 'Inicio',
                    'to' => 'DashboardStudent', // Debe coincidir con el 'name' en tu archivo Vue
                    'icon' => 'tabler-home',
                    // 'children' => [] // Si tuviera hijos se ponen aquí
                ],
                [
                    'title' => 'Mis Actividades',
                    'to' => 'ActivitiesStudent', // Nombre de la ruta Vue
                    'icon' => 'tabler-backpack', // Un icono de mochila queda bien
                ],
                // Aquí puedes agregar más items en el futuro (ej: 'Mis Notas', 'Horario')
            ];

            $arrayMenu = [];

            // B. Transformamos al formato que exige tu Frontend (con to.name e icon.icon)
            foreach ($rawMenu as $key => $value) {
                $arrayMenu[$key]['title'] = $value['title'];

                // Formateo de ruta
                $arrayMenu[$key]['to']['name'] = $value['to'];

                // Formateo de icono
                $arrayMenu[$key]['icon']['icon'] = $value['icon'] ?? 'mdi-circle-outline';

                // Lógica para hijos (Children) si existieran
                if (!empty($value['children'])) {
                    foreach ($value['children'] as $key2 => $value2) {
                        $arrayMenu[$key]['children'][$key2]['title'] = $value2['title'];
                        $arrayMenu[$key]['children'][$key2]['to']['name'] = $value2['to'];
                        // Puedes agregar lógica de iconos para hijos aquí si la necesitas
                    }
                }
            }

            return [
                'access_token' => $token->accessToken,
                'user' => $obj,
                'company' => $company,
                'menu' => $arrayMenu,
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

    public function sendResetLink(PassportAuthSendResetLinkRequest $request)
    {
        try {

            $user = $this->userRepository->findByEmail($request->input('email'));

            if (! $user) {
                $user = $this->teacherRepository->findByEmail($request->input('email'));
            }

            // Verificar si el usuario fue encontrado
            if (! $user) {
                return response()->json([
                    'code' => 404,
                    'message' => 'El usuario con ese correo electrónico no existe.',
                ], 404);
            }

            // Generar el enlace de restablecimiento
            $token = Password::getRepository()->create($user);

            $action_url = env('SYSTEM_URL_FRONT') . 'ResetPassword/' . $token . '?email=' . urlencode($request->input('email'));

            // Enviar el correo usando el job de Brevo
            BrevoProcessSendEmail::dispatch(
                emailTo: [
                    [
                        'name' => $user->full_name,
                        'email' => $request->input('email'),
                    ],
                ],
                subject: 'Link Restablecer Contraseña',
                templateId: 5,  // El ID de la plantilla de Brevo que quieres usar
                params: [
                    'full_name' => $user->full_name,
                    'bussines_name' => $user->company?->name,
                    'action_url' => $action_url,

                ],  // Aquí pasas los parámetros para la plantilla, por ejemplo, el texto del mensaje
            );

            return response()->json(['code' => 200, 'message' => 'Te hemos enviado por correo electrónico el enlace para restablecer tu contraseña.'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function passwordReset(Request $request)
    {
        try {
            // Validar los datos recibidos
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $response = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {

                    // Actualizar la contraseña del usuario
                    $user->password = $password;
                    $user->save();
                }
            );

            if ($response == Password::PASSWORD_RESET) {
                return response()->json([
                    'code' => 200,
                    'message' => 'La contraseña ha sido cambiada correctamente.',
                ]);
            }

            return response()->json([
                'code' => 400,
                'message' => 'El token de restablecimiento es inválido o ha expirado.',
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Algo Ocurrio, Comunicate Con El Equipo De Desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
}
