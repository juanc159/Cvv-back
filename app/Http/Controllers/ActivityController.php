<?php

namespace App\Http\Controllers;

use App\Enums\Activity\ActivityStatusEnum;
use App\Enums\Activity\ActivitySubmissionStatusEnum;
use App\Http\Requests\Activity\ActivityStoreRequest;
use App\Http\Resources\Activity\ActivityFormResource;
use App\Http\Resources\Activity\ActivityListPendingResource;
use App\Http\Resources\Activity\ActivityListResource;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use Illuminate\Support\Facades\Notification;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherComplementary;
use App\Notifications\BellNotification;
use App\Repositories\ActivityRepository;
use App\Repositories\TeacherRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Validation\Rules\Enum;

class ActivityController extends Controller
{
    public function __construct(
        protected TeacherRepository $teacherRepository,
        protected ActivityRepository $activityRepository,
        protected QueryController $queryController,
    ) {}

    public function list(Request $request)
    {
        try {

            // IMPORTANTE: teacher_id no debe venir del front
            $payload = array_merge($request->all(), [
                'teacher_id' => $request->input('user_id'),
            ]);

            $data = $this->activityRepository->paginate($payload);
            $tableData = ActivityListResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error Al Buscar Los Datos',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {

            $companyId = $request->input('company_id');
            $teacherId = $request->input('teacher_id');

            // Validación mínima: la empresa debe existir en payload
            if (empty($companyId)) {
                return response()->json([
                    'code' => 422,
                    'message' => 'company_id es requerido',
                ], 422);
            }

            // Validar que el teacher logueado pertenece a esa company
            // (evita que manden company_id de otra institución)
            $teacher = Teacher::query()
                ->select(['id', 'company_id'])
                ->where('id', $teacherId)
                ->firstOrFail();

            if ((string) $teacher->company_id !== (string) $companyId) {
                return response()->json([
                    'code' => 403,
                    'message' => 'Empresa no válida para este docente',
                ], 403);
            }


            $options = $this->teacherRepository->getTeacherActivityOptions($teacher->id);


            $activityStatusEnum = $this->queryController->selectActivityStatusEnum(request());

            return response()->json([
                'code' => 200,
                ...$activityStatusEnum,
                'data' => [
                    'grades' => $options['grades'],
                    'sections' => $options['sections'],
                    'subjects' => $options['subjects'],
                    'rules' => $options['rules'],
                ],
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error al cargar datos para crear actividad',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }


    public function store(ActivityStoreRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                // 1) datos validados (lo correcto)
                $payload = $request->validated();

                // 2) guardar por repositorio
                $activity = $this->activityRepository->store($payload);

                // 3) Notificar a los estudiantes si la actividad se publicó
                $this->notifyStudents($activity);

                return response()->json([
                    'code' => 200,
                    'message' => 'Actividad agregada correctamente',
                    'data' => ['id' => $activity->id],
                ]);
            });
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Algo ocurrió, comunícate con el equipo de desarrollo',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $activity = $this->activityRepository->find($id);
            $form = new ActivityFormResource($activity);

            $options = $this->teacherRepository->getTeacherActivityOptions($activity->teacher_id);

            $activityStatusEnum = $this->queryController->selectActivityStatusEnum(request());


            return response()->json([
                'code' => 200,
                'form' => $form,
                'data' => [
                    'grades' => $options['grades'],
                    'sections' => $options['sections'],
                    'subjects' => $options['subjects'],
                    'rules' => $options['rules'],
                ],
                ...$activityStatusEnum,


            ]);
        } catch (Throwable $th) {

            return response()->json(['code' => 500, $th->getMessage(), $th->getLine()]);
        }
    }

    public function update(ActivityStoreRequest $request, $id)
    {
        try {
            // Prevenimos la edición si la actividad ya está publicada.
            $activity = $this->activityRepository->find($id);
            if ($activity->status === ActivityStatusEnum::ACTIVITY_STATUS_002) {
                return response()->json(['code' => 403, 'message' => 'No se puede modificar una actividad que ya ha sido publicada.'], 403);
            }


            DB::beginTransaction();

            // Usamos el repositorio para actualizar la actividad
            $updatedActivity = $this->activityRepository->store($request->validated(), $id);

            if ($request->file('path')) {
                $file = $request->file('path');
                $path = $file->store('/activitys/activity_' . $updatedActivity->id . $request->input('path'), 'public');
                $updatedActivity->path = $path;
                $updatedActivity->save();
            }

            // Notificar a los estudiantes si la actividad se acaba de publicar
            $this->notifyStudents($updatedActivity);
            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Activity modificado correctamente']);
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

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $activity = $this->activityRepository->find($id);
            if ($activity) {
                $activity->delete();
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
                'message' => $th->getMessage(),
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $model = $this->activityRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Activity ' . $msg . ' con éxito']);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Endpoint para que el ESTUDIANTE vea sus actividades pendientes.
     * Ruta sugerida: GET /activity/pending
     */
    public function pending(Request $request)
    {
        try {
            $user = auth()->user();

            // 1. Validar que el usuario tenga perfil de estudiante
            // Ajusta 'student' si tu relación en el modelo User tiene otro nombre
            $student = $user->student;

            if (!$student) {
                return response()->json([
                    'code' => 403,
                    'message' => 'Acceso denegado: No se encontró un perfil de estudiante asociado a este usuario.'
                ], 403);
            }

            // 2. Obtener las coordenadas del alumno (Grado y Sección)
            // Asumimos que el user tiene company_id, si no, lo sacamos del estudiante
            $companyId = $user->company_id ?? $student->company_id;
            $gradeId = $student->grade_id;
            $sectionId = $student->section_id;

            // 3. Llamar al Repositorio (El método que creamos en el paso anterior)
            $data = $this->activityRepository->getStudentActivities($companyId, $gradeId, $sectionId, $student->id);

            // 4. Formatear la respuesta
            // Reutilizamos ActivityListResource para que el JSON sea consistente con lo que ya tienes
            $formattedData = ActivityListPendingResource::collection($data);

            return response()->json([
                'code' => 200,
                'message' => 'Actividades cargadas correctamente',
                'data' => $formattedData,
                // Metadatos de paginación manuales para el front
                'pagination' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                ]
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error al buscar actividades pendientes',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }


    /**
     * Función auxiliar para enviar notificaciones usando BellNotification
     */
    private function notifyStudents($activity)
    {
        // 1. Solo notificar si está PUBLICADO
        if ($activity->status === ActivityStatusEnum::ACTIVITY_STATUS_002) {

            // 2. Buscar a los ESTUDIANTES del curso
            // Asumimos que el estudiante tiene una relación 'user' (belongsTo User)
            // Necesitamos traer el 'user' porque BellNotification trabaja con Users
            $students = Student::with('user')
                ->where('company_id', $activity->company_id)
                ->where('grade_id', $activity->grade_id)
                ->where('section_id', $activity->section_id)
                ->get();

            // 3. Extraer los USUARIOS de esos estudiantes
            // Filtramos aquellos estudiantes que no tengan usuario asociado para evitar errores
            $usersToNotify = $students->pluck('user')->filter();

            if ($usersToNotify->isEmpty()) {
                return;
            }

            // 4. Preparar la data con la estructura que exige BellNotification
            $notificationData = [
                'title' => 'Nueva Actividad: ' . $activity->title,
                'subtitle' => ($activity->subject->name ?? 'Materia') . ' - Vence: ' . ($activity->deadline_at ? $activity->deadline_at->setTimezone(config('app.timezone'))->format('d/m/Y h:i A') : 'Sin fecha'),
                'action_url' => '/student/activities', // Ruta del front donde verá la lista
                'openInNewTab' => false,
                'img' => null, // Dejamos null para que tu lógica use la foto del usuario/empresa o null
                'text' => 'Tarea', // Texto corto para el avatar si no hay imagen
            ];

            // 5. Enviar la notificación masiva
            Notification::send($usersToNotify, new BellNotification($notificationData));
        }
    }

    public function submitActivity(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $student = $user->student;

            if (!$student) {
                return response()->json(['code' => 403, 'message' => 'No se encontró un perfil de estudiante asociado.'], 403);
            }

            // 1. Validar
            $request->validate([
                'activity_id' => 'required|exists:activities,id',
                'comments'    => 'nullable|string',
                'links'       => 'nullable|array',
                'is_draft'    => 'boolean', // Recibimos si es borrador o entrega final
            ]);

            // Definir el estado destino (Borrador 001 o Entregado 002)
            $targetStatus = $request->boolean('is_draft')
                ? ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_001
                : ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_002;

            // 2. Buscar último intento
            $lastSubmission = ActivitySubmission::where('activity_id', $request->activity_id)
                ->where('student_id', $student->id)
                ->orderBy('attempt_number', 'desc')
                ->first();

            $submission = null;
            $message = '';

            // ---------------------------------------------------------
            // ESCENARIO A: YA EXISTE UNA ENTREGA PREVIA
            // ---------------------------------------------------------
            if ($lastSubmission) {

                // 1. SI ES UN BORRADOR (001) -> ¡LO ACTUALIZAMOS!
                // No creamos uno nuevo, simplemente editamos el existente.
                if ($lastSubmission->status === ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_001) {

                    $lastSubmission->update([
                        'comments' => $request->comments,
                        'links'    => $request->links,
                        'status'   => $targetStatus, // Aquí puede cambiar de Borrador a Entregado
                    ]);

                    $submission = $lastSubmission;
                    $message = $request->boolean('is_draft') ? 'Borrador actualizado correctamente.' : '¡Tarea entregada con éxito!';
                }
                // 2. SI YA ESTÁ ENTREGADO (002) -> BLOQUEAMOS
                elseif ($lastSubmission->status === ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_002) {
                    return response()->json([
                        'code' => 422,
                        'message' => 'Ya tienes una entrega en revisión. Debes esperar respuesta del docente.'
                    ], 422);
                }
                // 3. SI YA ESTÁ APROBADO (004) -> BLOQUEAMOS
                elseif ($lastSubmission->status === ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_004) {
                    return response()->json([
                        'code' => 422,
                        'message' => 'Esta actividad ya fue aprobada y cerrada.'
                    ], 422);
                }
                // 4. SI FUE DEVUELTO (003) -> CREAMOS NUEVO INTENTO
                elseif ($lastSubmission->status === ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_003) {

                    // Solo permitimos crear nuevo si NO estamos tratando de guardar otro borrador del intento anterior
                    // (Lógica de nuevo intento)
                    $newAttempt = $lastSubmission->attempt_number + 1;

                    $submission = ActivitySubmission::create([
                        'activity_id'    => $request->activity_id,
                        'student_id'     => $student->id,
                        'comments'       => $request->comments,
                        'links'          => $request->links,
                        'attempt_number' => $newAttempt,
                        'status'         => $targetStatus,
                    ]);
                    $message = 'Corrección enviada correctamente (Intento #' . $newAttempt . ')';
                }
            }
            // ---------------------------------------------------------
            // ESCENARIO B: NO EXISTE NADA -> CREAMOS PRIMER INTENTO
            // ---------------------------------------------------------
            else {
                $submission = ActivitySubmission::create([
                    'activity_id'    => $request->activity_id,
                    'student_id'     => $student->id,
                    'comments'       => $request->comments,
                    'links'          => $request->links,
                    'attempt_number' => 1,
                    'status'         => $targetStatus,
                ]);
                $message = $request->boolean('is_draft') ? 'Borrador guardado.' : '¡Tarea entregada con éxito!';
            }

            // Opcional: Notificar al docente solo si NO es borrador
            // if (!$request->boolean('is_draft')) { ... }

            DB::commit();

            return response()->json([
                'code' => 200,
                'message' => $message,
                'data' => $submission
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => 'Error al procesar la entrega',
                'error' => $th->getMessage()
            ], 500);
        }
    }



    /**
     * Endpoint: GET /student/activity/{id}
     * Muestra el detalle de la actividad al alumno y su estado de entrega.
     */
    public function showForStudent($id)
    {
        try {
            $user = auth()->user();
            $student = $user->student;

            // 1. CARGAR ACTIVIDAD (FICHA TÉCNICA)
            $activity = Activity::with(['subject', 'teacher'])->findOrFail($id);

            // Seguridad de Grado/Sección
            if ($activity->grade_id != $student->grade_id || $activity->section_id != $student->section_id) {
                return response()->json(['code' => 403, 'message' => 'No tienes permiso.'], 403);
            }

            // 2. CARGAR HISTORIAL COMPLETO DE ENTREGAS
            // Traemos todos los intentos ordenados cronológicamente
            $submissionsHistory = ActivitySubmission::where('activity_id', $id)
                ->where('student_id', $student->id)
                ->orderBy('attempt_number', 'desc')
                ->get();

            // Obtenemos el último intento (si existe) para decidir qué hacer ahora
            $lastSubmission = $submissionsHistory->first();

            // 3. LÓGICA DE ESTADO DEL FORMULARIO
            // Valores por defecto (Escenario: Primera vez que entra)
            $formState = [
                'can_edit' => true,           // ¿El alumno puede escribir/guardar?
                'mode'     => 'create',       // 'create' (nuevo intento) o 'edit' (borrador)
                'data'     => [               // Datos para pre-llenar el formulario
                    'comments' => '',
                    'links'    => [],
                ],
                'status_label' => 'Pendiente', // Texto para mostrar al usuario
                'status_color' => 'secondary', // Color del badge
                'next_attempt' => 1,           // El número de intento que toca hacer
            ];

            $isOverdue = $activity->deadline_at ? now()->gt($activity->deadline_at) : false;

            if ($lastSubmission) {
                $status = $lastSubmission->status;

                // Actualizamos etiqueta y color basado en el último estado
                $formState['status_label'] = $status->description();
                $formState['status_color'] = $status->color();

                // --- ESCENARIO A: BORRADOR (001) ---
                if ($status === ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_001) {
                    $formState['can_edit'] = true; // Puede editar el borrador
                    $formState['mode']     = 'edit'; // Editamos el existente
                    $formState['next_attempt'] = $lastSubmission->attempt_number;
                    // PRE-LLENAMOS EL FORMULARIO CON LO QUE GUARDÓ
                    $formState['data'] = [
                        'comments' => $lastSubmission->comments,
                        'links'    => $lastSubmission->links ?? [],
                    ];
                }

                // --- ESCENARIO B: ENTREGADO (002) O APROBADO (004) ---
                elseif (in_array($status, [
                    ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_002,
                    ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_004
                ])) {
                    $formState['can_edit'] = false; // BLOQUEADO por estar en revisión o ya calificado
                    $formState['mode']     = 'locked';
                    $formState['next_attempt'] = $lastSubmission->attempt_number;
                    // Mostramos lo que envió, pero en modo lectura
                    $formState['data'] = [
                        'comments' => $lastSubmission->comments,
                        'links'    => $lastSubmission->links ?? [],
                    ];
                }

                // --- ESCENARIO C: REQUIERE CORRECCIÓN (003) ---
                elseif ($status === ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_003) {
                    $formState['can_edit'] = true; // Puede crear una nueva entrega
                    $formState['mode']     = 'create'; // Creamos uno NUEVO
                    $formState['next_attempt'] = $lastSubmission->attempt_number + 1;
                    // DEJAMOS EL FORMULARIO VACÍO PARA EL NUEVO INTENTO
                    $formState['data'] = [
                        'comments' => '',
                        'links'    => [],
                    ];
                    // Opcional: Si quisieras copiar los datos del anterior para que solo edite,
                    // aquí llenarías $formState['data'] con $lastSubmission.
                }
            }

            // --- LÓGICA DE VENCIMIENTO (SOBREESCRIBE LO ANTERIOR SI APLICA) ---
            // Si está vencida Y el formulario aún es editable (es decir, no está 'Revisado' o 'Entregado'),
            // entonces lo bloqueamos y mostramos el mensaje específico.
            if ($isOverdue && $formState['can_edit']) {
                $formState['can_edit'] = false;
                $formState['mode'] = 'locked';
                $formState['status_label'] = 'Bloqueado por fecha de entrega vencida';
                $formState['status_color'] = 'error';
            }

            // 4. PREPARAR RESPUESTA FINAL
            $response = [
                // Info de la Actividad (Estática)
                'activity' => [
                    'id'          => $activity->id,
                    'title'       => $activity->title,
                    'description' => $activity->description,
                    'deadline_at' => $activity->deadline_at ? $activity->deadline_at->setTimezone(config('app.timezone'))->format('d/m/Y h:i A') : null,
                    'subject'     => $activity->subject,
                    'teacher'     => $activity->teacher, 
                    'is_overdue'  => $isOverdue,
                ],

                // Estado Actual del Formulario (Dinámico)
                'current_state' => $formState,

                // Historial (Para mostrar timeline de intentos abajo)
                'history' => $submissionsHistory
                    // ->filter(function ($sub) {
                    //     // EXCLUIR BORRADORES: Solo dejamos pasar los que NO sean 001
                    //     return $sub->status !== ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_001;
                    // })
                    ->map(function ($sub) {
                        return [
                            'id'             => $sub->id,
                            'attempt_number' => $sub->attempt_number,
                            'status_label'   => $sub->status->description(),
                            'status_color'   => $sub->status->color(),
                            'submitted_at'   => $sub->created_at->format('d/m/Y H:i'),
                            'comments'       => $sub->comments,
                            'links'          => $sub->links,
                        ];
                    })
                    ->values(),
            ];

            return response()->json(['code' => 200, 'data' => $response]);
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Endpoint: GET /teacher/activities/{id}/submissions
     * Devuelve la actividad, los stats y la lista cruzada de alumnos con sus entregas.
     */
    public function getSubmissions($id)
    {
        try {
            $teacher = auth()->user()->teacher;

            $activity = Activity::with(['grade', 'section', 'subject'])->findOrFail($id);

            if ($activity->teacher_id !== $teacher->id) {
                return response()->json(['code' => 403, 'message' => 'No tienes permiso para ver esta actividad.'], 403);
            }

            $students = Student::with('user')
                ->where('grade_id', $activity->grade_id)
                ->where('section_id', $activity->section_id) 
                ->get();

            $submissions = ActivitySubmission::where('activity_id', $id)
                ->get()
                ->groupBy('student_id');

            $stats = ['total' => $students->count(), 'submitted' => 0, 'reviewed' => 0, 'pending' => 0];

            $studentsList = $students->map(function ($student) use ($submissions, &$stats) {
                $studentSubmissions = $submissions->get($student->id);

                // Ordenamos para tener el más reciente primero
                $latestSubmission = $studentSubmissions ? $studentSubmissions->sortByDesc('attempt_number')->first() : null;
                $statusValue = $latestSubmission ? $latestSubmission->status->value : null;

                if ($statusValue === \App\Enums\Activity\ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_002->value) {
                    $stats['submitted']++;
                } elseif ($statusValue === \App\Enums\Activity\ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_004->value) {
                    $stats['reviewed']++;
                } else {
                    $stats['pending']++;
                }

                $fullName = $student->user ? ($student->user->first_name . ' ' . $student->user->last_name) : 'Alumno Desconocido';

                // --- NUEVO: CREAMOS EL HISTORIAL COMPLETO PARA LAS PESTAÑAS ---
                $history = $studentSubmissions ? $studentSubmissions->sortByDesc('attempt_number')->map(function ($sub) {
                    return [
                        'id'             => $sub->id,
                        'comments'       => $sub->comments,
                        'links'          => $sub->links,
                        'attempt_number' => $sub->attempt_number,
                        'status'         => $sub->status->value,
                        'status_label'   => $sub->status->description(),
                        'status_color'   => $sub->status->color(),
                        'submitted_at'   => $sub->created_at->format('Y-m-d h:i A'),
                    ];
                })->values()->toArray() : [];

                return [
                    'id'             => $student->id,
                    'student_name'   => $fullName,
                    'avatar'         => substr($fullName, 0, 1),
                    'status'         => $statusValue,
                    'status_label'   => $latestSubmission ? $latestSubmission->status->description() : 'Sin Entregar',
                    'status_color'   => $latestSubmission ? $latestSubmission->status->color() : 'grey',
                    'submitted_at'   => $latestSubmission ? $latestSubmission->created_at->format('Y-m-d h:i A') : null,
                    'attempt_number' => $latestSubmission ? $latestSubmission->attempt_number : 0,
                    'submission'     => $latestSubmission ? ['id' => $latestSubmission->id] : null,
                    'history'        => $history // <--- ENVIAMOS EL HISTORIAL AL FRONT
                ];
            });

            return response()->json([
                'code' => 200,
                'data' => [
                    'activity' => [
                        'id'          => $activity->id,
                        'title'       => $activity->title,
                        'subject'     => $activity->subject?->name ?? 'Sin Materia',
                        'grade'       => $activity->grade?->name ?? '',
                        'section'     => $activity->section?->name ?? '',
                        'deadline_at' => $activity->deadline_at ? $activity->deadline_at->format('Y-m-d') : null,
                    ],
                    'stats'        => $stats,
                    'studentsList' => $studentsList->values()->toArray()
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['code' => 500, 'message' => 'Error al cargar las entregas', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Endpoint: PUT /teacher/submissions/{id}/status
     * Actualiza el estado de una entrega (Aprobar o Devolver).
     */
    public function evaluateSubmission(Request $request, $id)
    {
        try {
            // 1. Validar que el estado enviado sea válido dentro de nuestro Enum
            $request->validate([
                'status' => ['required', new Enum(ActivitySubmissionStatusEnum::class)]
            ]);

            // 2. Buscar la entrega y cargar la actividad asociada
            $submission = ActivitySubmission::with('activity')->findOrFail($id);

            // 3. CAPA DE SEGURIDAD: Verificar que el profesor logueado sea el dueño de esta actividad
            $teacher = auth()->user()->teacher; // Ajusta según tu lógica de autenticación

            if ($submission->activity->teacher_id !== $teacher->id) {
                return response()->json([
                    'code' => 403,
                    'message' => 'No tienes permiso para evaluar entregas de esta actividad.'
                ], 403);
            }

            // 4. Actualizar el estado de la entrega
            $submission->update([
                'status' => $request->status
            ]);

            // --- OPCIONAL: Notificaciones ---
            // Si el estado es 003 (Devuelto), podrías disparar una notificación al alumno
            // Si el estado es 004 (Aprobado), también.

            return response()->json([
                'code' => 200,
                'message' => 'Evaluación guardada correctamente.',
                'data' => $submission
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error al procesar la evaluación.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint: GET /teacher/activities/{id}/submissions
     * Devuelve el dashboard y la lista de alumnos SIN el historial pesado.
     */
    public function getSubmissionsList($id)
    {
        try {
            $teacher = auth()->user()->teacher;

            // 1. Cargar Actividad
            $activity = Activity::with(['grade', 'section', 'subject'])->findOrFail($id);

            if ($activity->teacher_id !== $teacher->id) {
                return response()->json(['code' => 403, 'message' => 'No tienes permiso.'], 403);
            }

            // 2. Cargar Alumnos (ordenados por nombre completo)
            $students = Student::with('user') // Optimización: Solo traer nombre del usuario
                ->where('grade_id', $activity->grade_id)
                ->where('section_id', $activity->section_id)
                ->orderBy('full_name', 'asc')
                ->get();

            // 3. OPTIMIZACIÓN SQL: Traer entregas pero SOLO los campos necesarios para la lista.
            // Ignoramos 'comments' y 'links' para no saturar la memoria RAM del servidor.
            $submissions = ActivitySubmission::select('id', 'activity_id', 'student_id', 'status', 'attempt_number', 'created_at')
                ->where('activity_id', $id)
                ->get()
                ->groupBy('student_id');

            $stats = ['total' => $students->count(), 'submitted' => 0, 'reviewed' => 0, 'pending' => 0];

            // 4. Mapear Lista
            $studentsList = $students->map(function ($student) use ($submissions, &$stats) {
                $studentSubmissions = $submissions->get($student->id);
                $latestSubmission = $studentSubmissions ? $studentSubmissions->sortByDesc('attempt_number')->first() : null;
                $statusValue = $latestSubmission ? $latestSubmission->status->value : null;

                // Calcular stats
                if ($statusValue === \App\Enums\Activity\ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_002->value) {
                    $stats['submitted']++;
                } elseif ($statusValue === \App\Enums\Activity\ActivitySubmissionStatusEnum::ACTIVITY_SUBMISSION_STATUS_004->value) {
                    $stats['reviewed']++;
                } else {
                    $stats['pending']++;
                }

                $fullName = $student->full_name;

                return [
                    'id'             => $student->id,
                    'student_name'   => $fullName,
                    'avatar'         => $student->photo,
                    'initials' => getInitials($fullName),
                    'status'         => $statusValue,
                    'status_label'   => $latestSubmission ? $latestSubmission->status->description() : 'Sin Entregar',
                    'status_color'   => $latestSubmission ? $latestSubmission->status->color() : 'grey',
                    'submitted_at'   => $latestSubmission ? $latestSubmission->created_at->format('Y-m-d h:i A') : null,
                    'attempt_number' => $latestSubmission ? $latestSubmission->attempt_number : 0,

                    // Solo mandamos el ID de la entrega para usarlo después, nada de textos ni links
                    'submission'     => $latestSubmission ? ['id' => $latestSubmission->id] : null,
                ];
            });

            return response()->json([
                'code' => 200,
                'data' => [
                    'activity'     => $activity->only(['id', 'title', 'deadline_at']) + [
                        'subject' => $activity->subject?->name ?? '',
                        'grade'   => $activity->grade?->name ?? '',
                        'section' => $activity->section?->name ?? '',
                    ],
                    'stats'        => $stats,
                    'studentsList' => $studentsList->values()->toArray()
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['code' => 500, 'message' => 'Error al cargar lista.', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Endpoint: GET /teacher/activities/{activity_id}/students/{student_id}/history
     * Devuelve TODO el historial de intentos con comentarios y links de un solo alumno.
     */
    public function getStudentHistory($activityId, $studentId)
    {
        try {
            // Seguridad básica
            $teacher = auth()->user()->teacher;
            $activity = Activity::findOrFail($activityId);

            if ($activity->teacher_id !== $teacher->id) {
                return response()->json(['code' => 403, 'message' => 'No tienes permiso.'], 403);
            }

            // Aquí SÍ traemos todos los campos pesados (comments, links)
            $history = ActivitySubmission::where('activity_id', $activityId)
                ->where('student_id', $studentId)
                ->orderBy('attempt_number', 'desc')
                ->get();

            // Formateamos la respuesta
            $formattedHistory = $history->map(function ($sub) {
                return [
                    'id'             => $sub->id,
                    'attempt_number' => $sub->attempt_number,
                    'comments'       => $sub->comments,
                    'links'          => $sub->links,
                    'status'         => $sub->status->value,
                    'status_label'   => $sub->status->description(),
                    'status_color'   => $sub->status->color(),
                    'submitted_at'   => $sub->created_at->format('Y-m-d h:i A'),
                ];
            });

            return response()->json([
                'code' => 200,
                'data' => $formattedHistory
            ]);
        } catch (\Throwable $th) {
            return response()->json(['code' => 500, 'message' => 'Error al cargar historial.', 'error' => $th->getMessage()], 500);
        }
    }
}
