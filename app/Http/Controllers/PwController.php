<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Http\Resources\Company\CompanyPwSchoolResource;
use App\Models\Banner;
use App\Models\Company;
use App\Models\CompanyDetail;
use App\Models\Teacher;
use App\Models\TeacherPlanning;
use App\Models\Term;
use App\Repositories\BannerRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\GradeRepository;
use App\Repositories\PendingRegistrationRepository;
use App\Repositories\SectionRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TeacherPlanningRepository;
use App\Repositories\TypeEducationRepository;
use App\Services\CacheService;
use App\Traits\HttpResponseTrait;
use Carbon\Carbon;

class PwController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private CompanyRepository $companyRepository,
        private BannerRepository $bannerRepository,
        private TypeEducationRepository $typeEducationRepository,
        private GradeRepository $gradeRepository,
        private SectionRepository $sectionRepository,
        private StudentRepository $studentRepository,
        private TeacherComplementaryRepository $teacherComplementaryRepository,
        private SubjectRepository $subjectRepository,
        private ServiceRepository $serviceRepository,
        private TeacherPlanningRepository $teacherPlanningRepository,
        private PendingRegistrationRepository $pendingRegistrationRepository,
        private CacheService $cacheService,
    ) {
        $this->companyRepository = $companyRepository;
        $this->bannerRepository = $bannerRepository;
        $this->typeEducationRepository = $typeEducationRepository;
        $this->gradeRepository = $gradeRepository;
        $this->sectionRepository = $sectionRepository;
        $this->studentRepository = $studentRepository;
        $this->teacherComplementaryRepository = $teacherComplementaryRepository;
        $this->subjectRepository = $subjectRepository;
        $this->serviceRepository = $serviceRepository;
        $this->teacherPlanningRepository = $teacherPlanningRepository;
    }

    public function dataPrincipal()
    {
        $cacheKey = $this->cacheService->generateKey("banners_listPw", ['typeData' => 'all', 'company_id' => null], 'string');
        $banners = $this->cacheService->remember($cacheKey, function () {
            return $this->bannerRepository->list(['typeData' => 'all', 'company_id' => null], select: ['id', 'path']);
        }, Constants::REDIS_TTL);


        $cacheKey = $this->cacheService->generateKey("companies_listPw", ['typeData' => 'all'], 'string');
        $companies = $this->cacheService->remember($cacheKey, function () {
            return $this->companyRepository->list(['typeData' => 'all'], select: ['id', 'name', 'image_principal']);
        }, Constants::REDIS_TTL);



        return response()->json([
            'banners' => $banners,
            'companies' => $companies,
        ]);
    }

    public function dataSchool($id)
    {
        $company = $this->companyRepository->find($id);
        $company = new CompanyPwSchoolResource($company);

        $typeEducations = $this->typeEducationRepository->selectList();

        // BACHILLERATO
        $generalSecondaryEducation = $this->teacherComplementaryRepository->get()->groupBy(function ($item) {
            return $item->grade->name;
        })->map(function ($grades) use ($id) {
            return $grades->groupBy(function ($item) {
                return $item->section->name;
            })->map(function ($sections) use ($id) {
                $info = $sections->first();
                // Filtrar por type_education_id == 3
                if ($info->teacher?->type_education_id == 3 && $info->teacher?->company_id == $id) {
                    return [
                        'grade_id' => $info->grade->id,
                        'grade_name' => $info->grade->name,
                        'section_id' => $info->section->id,
                        'section_name' => $info->section->name,
                    ];
                }

                return null; // No cumple con la condición, devuelve null
            })->filter(); // Elimina los elementos nulos del resultado
        })->reject(function ($sections) {
            // Rechaza las secciones con un array vacío
            return $sections->isEmpty();
        });

        return response()->json([
            'company' => $company,
            'typeEducations' => $typeEducations,
            'generalSecondaryEducation' => $generalSecondaryEducation,
        ]);
    }

    public function dataGradeSection($school_id, $grade_id, $section_id)
    {
        $teacherComplementaries = $this->teacherComplementaryRepository->list([
            'typeData' => 'all',
            'grade_id' => $grade_id,
            'section_id' => $section_id,
            'company_id' => $school_id,
        ], ['teacher' => function ($query) use ($school_id) {
            $query->where('company_id', $school_id);
        }]);

        $teachers = [];

        $color = generarColorPastelAleatorio(70);
        foreach ($teacherComplementaries as $key => $value) {
            $subjects = explode(',', $value['subject_ids']);
            foreach ($subjects as $sub) {
                $subject = $this->subjectRepository->find($sub);

                if ($subject) {

                    $files = TeacherPlanning::where(function ($query) use ($value) {
                        $query->where('teacher_id', $value->teacher_id);
                        $query->where('grade_id', $value->grade_id);
                        $query->where('section_id', $value->section_id);
                        // $query->where('subject_id', $subject->id);
                    })->get()->map(function ($f) {
                        return [
                            'name' => $f->name,
                            'path' => $f->path,
                            'id' => $f->id,
                        ];
                    });

                    $teachers[] = [
                        'subject_name' => $subject->name,
                        'fullName' => $value['teacher']['name'] . ' ' . $value['teacher']['last_name'],
                        'photo' => $value['teacher']['photo'],
                        'email' => $value['teacher']['email'],
                        'phone' => $value['teacher']['phone'],
                        'jobPosition' => $value['teacher']['jobPosition']['name'],
                        'files' => $files,
                        'backgroundColor' => $color,
                    ];
                }
            }
        }

        $grade = $this->gradeRepository->find($grade_id);
        $section = $this->sectionRepository->find($section_id);

        $title = $grade->name . ' ' . $section->name;

        return response()->json([
            'teachers' => $teachers,
            'title' => $title,
        ]);
    }

    public function dataGradeSectionNotes($school_id, $grade_id, $section_id)
    {
        $students = $this->studentRepository->list([
            'typeData' => 'all',
            'grade_id' => $grade_id,
            'section_id' => $section_id,
            'company_id' => $school_id,
        ], ['notes'])->map(function ($value) {
            return [
                'id' => $value->id,
                'full_name' => $value->full_name,
                'identity_document' => $value->identity_document,
                'pdf' => $value->pdf,
                'photo' => $value->photo,
            ];
        });

        $grade = $this->gradeRepository->find($grade_id);
        $section = $this->sectionRepository->find($section_id);

        $title = $grade->name . ' ' . $section->name;

        return response()->json([
            'students' => $students,
            'title' => $title,
        ]);
    }

    public function pdfPNote($id)
    {
        try {
            // Verificar que Carbon::now() devuelva una instancia válida
            $currentDate = Carbon::now();

            // Configurar el locale y formatear
            $currentDate->setLocale('es');
            $formattedDate = $currentDate->translatedFormat('l, j \\de F \\de Y');

            // Resto del código...
            $student = $this->studentRepository->find($id, [
                'notes.subject',
                'grade.subjects',
            ]);

            if (! $student) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Estudiante no encontrado',
                ], 404);
            }

            $subjectIds = $student->grade->subjects->pluck('id');
            $filteredNotes = $student->notes()
                ->whereIn('subject_id', $subjectIds)
                ->with('subject')
                ->get();

            /// Obtener materias pendientes con sus intentos
            $pendingAttempts = $student->pendingRegistrationAttempts()
                ->with('subject')
                ->get()
                ->groupBy('subject_id')
                ->map(function ($attempts) {
                    return $attempts->sortBy('attempt_number');
                });

            $pdfContent = $this->studentRepository->pdf(
                'Pdfs.StudentNote',
                [
                    'student' => $student,
                    'filteredNotes' => $filteredNotes,
                    'date' => $formattedDate,
                    'pendingAttempts' => $pendingAttempts,
                ],
                'Notas',
                false
            );

            if (! $pdfContent) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Error al generar el PDF',
                ], 500);
            }

            $pdfBase64 = base64_encode($pdfContent);

            return response()->json([
                'code' => 200,
                'pdf' => $pdfBase64,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(), // Para depuración
                'line' => $th->getLine(),     // Para depuración
            ], 500);
        }
    }

    public function linksMenu($company_id)
    {
        try {
            $detalles = CompanyDetail::where('company_id', $company_id)->get()->map(function ($value) {
                return [
                    'type_detail_id' => $value->type_detail_id,
                    'type_detail_name' => $value->typeDetail?->name,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => $value->content,
                ];
            });

            $social_networks = $detalles->whereIn('type_detail_id', [1, 2, 3, 4, 5])->values();
            $links = $detalles->whereIn('type_detail_id', [10, 11, 12])->values();

            return response()->json([
                'code' => 200,
                'social_networks' => $social_networks,
                'links' => $links,
            ]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function banners($company_id)
    {
        try {
            $cacheKey = $this->cacheService->generateKey("banners_wherePw", ["company_id" => $company_id], 'string');

            $banners = $this->cacheService->remember($cacheKey, function () use ($company_id) {
                return Banner::select('path')->where('company_id', $company_id)->where('is_active', 1)->get();
            }, Constants::REDIS_TTL);



            return response()->json(['code' => 200, 'banners' => $banners]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function teachers($company_id)
    {
        try {
            $cacheKey = $this->cacheService->generateKey("teachers_wherePw", ["company_id" => $company_id], 'string');

            $teachers = $this->cacheService->remember($cacheKey, function () use ($company_id) {
                return Teacher::where('company_id', $company_id)->where('is_active', 1)->orderBy('order')->get()->map(function ($value) {
                    $grade_name = '';
                    $section_name = '';

                    $info = $value->complementaries->first();
                    if ($info) {
                        $grade_name = $info->grade?->name;
                        $section_name = $info->section?->name;
                    }

                    return [
                        'id' => $value->id,
                        'fullName' => $value->name . ' ' . $value->last_name,
                        'photo' => $value->photo,
                        'type_education_id' => $value->type_education_id,
                        'type_education_name' => $value->typeEducation?->name,
                        'email' => $value->email,
                        'phone' => $value->phone,
                        'job_position_id' => $value->job_position_id,
                        'jobPosition' => $value->jobPosition?->name,
                        'backgroundColor' => generarColorPastelAleatorio(70),
                        'grade_name' => $grade_name,
                        'section_name' => $section_name,
                    ];
                });
            }, Constants::REDIS_TTL);


            $tabsData = [];

            $data = $teachers->whereIn('job_position_id', [Constants::MANAGERS_UUID, Constants::COORDINATORS_UUID]);
            $tabsData[] = [
                'title' => 'Directivos y coordinadores',
                'number_records' => $data->count(),
                'data' => $data->values(),
            ];

            $data = $teachers->whereIn('type_education_id', Constants::INITIAL_EDUCATION_UUID)->where('job_position_id', 3);
            $tabsData[] = [
                'title' => 'Educación inicial',
                'number_records' => $data->count(),
                'data' => $data->values(),
            ];

            $data = $teachers->whereIn('type_education_id', Constants::PRIMARY_EDUCATION_UUID)->where('job_position_id', Constants::TEACHERS_UUID);
            $tabsData[] = [
                'title' => 'Educación primaria',
                'number_records' => $data->count(),
                'data' => $data->values(),
            ];

            $data = $teachers->whereIn('type_education_id', Constants::GENERAL_SECONDARY_EDUCATION_UUID)->where('job_position_id', Constants::TEACHERS_UUID);
            $tabsData[] = [
                'title' => 'Educación media general',
                'number_records' => $data->count(),
                'data' => $data->values(),
            ];

            $data = $teachers->whereIn('job_position_id', Constants::SPECIALISTS_UUID);
            $tabsData[] = [
                'title' => 'Especialistas',
                'number_records' => $data->count(),
                'data' => $data->values(),
            ];

            return response()->json(['code' => 200, 'tabsData' => $tabsData]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function contactData($company_id)
    {
        try {
            $cacheKey = $this->cacheService->generateKey("teachers_findPw", ["company_id" => $company_id], 'string');
            $company = $this->cacheService->remember($cacheKey, function () use ($company_id) {
                return Company::with([
                    'details' => function ($q) {
                        $q->whereIn('type_detail_id', [6, 7, 8, 13, 14, 15]);
                    },
                ])->find($company_id);
            }, Constants::REDIS_TTL);



            $contactData['details'] = $company->details?->map(function ($value) {
                return [
                    'type_detail_name' => $value->typeDetail?->name,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => strpos($value->content, '|') !== false ? explode('|', $value->content) : $value->content,
                ];
            });

            $contactData['iframeGoogleMap'] = $company->iframeGoogleMap;

            return response()->json(['code' => 200, 'contactData' => $contactData]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function services($company_id)
    {
        try {

            $filter = [
                'typeData' => 'all',
                'is_active' => 1,
                'company_id' => $company_id,
            ];
            $cacheKey = $this->cacheService->generateKey("services_listPw", $filter, 'string');
            $services = $this->cacheService->remember($cacheKey, function () use ($filter) {
                return $this->serviceRepository->list($filter, select: ['id', 'title', 'image']);
            }, Constants::REDIS_TTL);


            $services->map(function ($value) {
                return [
                    'id' => $value->id,
                    'title' => $value->title,
                    'image' => $value->image,
                ];
            });

            return response()->json(['code' => 200, 'services' => $services]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function service($service_id)
    {
        try {
            $service = $this->serviceRepository->find($service_id);

            return response()->json(['code' => 200, 'service' => $service]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }


    public function materiaPendiente($company_id)
    {
        return $this->runTransaction(function () use ($company_id) {

            // Buscar los pending_registrations asociados a la compañía
            $pendingRegistrations = $this->pendingRegistrationRepository
                ->findByCompanyId($company_id)
                ->map(function ($pendingRegistration) {
                    // Agrupar archivos por subject_name
                    $groupedFiles = $pendingRegistration->files
                        ->map(function ($file) {
                            return [
                                'id' => $file->id,
                                'subject_id' => $file->subject_id,
                                'subject_name' => $file->subject?->name ?? 'Sin materia', // Valor por defecto si no hay materia
                                'path' => $file->path,
                                'name' => $file->name,
                                'created_at' => $file->created_at->toDateTimeString(),
                                'updated_at' => $file->updated_at->toDateTimeString(),
                            ];
                        })
                        ->groupBy('subject_name')
                        ->map(function ($files, $subject_name) {
                            return [
                                'subject_name' => $subject_name,
                                'files' => $files->map(function ($file) {
                                    // Excluimos subject_name del objeto file para evitar redundancia
                                    return [
                                        'id' => $file['id'],
                                        'subject_id' => $file['subject_id'],
                                        'path' => $file['path'],
                                        'name' => $file['name'],
                                        'created_at' => $file['created_at'],
                                        'updated_at' => $file['updated_at'],
                                    ];
                                }),
                            ];
                        })
                        ->values(); // Convertimos a un array indexado

                    return [
                        'id' => $pendingRegistration->id,
                        'term_name' => $pendingRegistration->term?->name,
                        'grade_name' => $pendingRegistration->grade?->name,
                        'section_name' => $pendingRegistration->section_name,
                        'files_by_subject' => $groupedFiles, // Nueva clave con los archivos agrupados
                    ];
                });

            $term_name = $pendingRegistrations->first();
            if ($term_name) {
                $term_name = $term_name["term_name"];
            }

            $company = $this->companyRepository->find($company_id);
            return [
                'code' => 200,
                'pendingRegistrations' => $pendingRegistrations,
                'term_name' => $term_name,
                'students_pending_subject' => $company->students_pending_subject,
            ];
        });
    }

    public function pdfSolvencyCertificate($id)
    {
        try {
            // Datos del estudiante
            $student = $this->studentRepository->find($id, [
                'type_education:id,name',
                'grade:id,name',
                'section:id,name',
                'type_document:id,name',
            ], select: ["id", "full_name", "identity_document", "photo", "type_education_id", "grade_id", "section_id", "type_document_id"]);

            if (!$student) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Estudiante no encontrado',
                ], 404);
            }

            // Procesar el tipo de documento
            $type_document = $student->type_document?->name;
            $type_document_name = "";
            switch ($type_document) {
                case 'Cédula de identidad':
                    $type_document_name = " de la cédula de identidad";
                    break;
                case 'Cédula escolar':
                    $type_document_name = " de la cédula escolar";
                    break;
                case 'Número de pasaporte':
                    $type_document_name = " del Número de pasaporte";
                    break;
                default:
                    $type_document_name = " del documento ";
                    break;
            }

            // Generar el código de solvencia dinámicamente
            // 1. Primera letra del tipo de educación 
            $educationPrefix = '';
            $words = explode(' ', $student->type_education?->name ?? '');
            foreach ($words as $word) {
                if (!empty($word)) {
                    $educationPrefix .= strtoupper(substr($word, 0, 1));
                }
            }

            // 2. Código del grado y sección (ejemplo: "4F")
            $gradeName = $student->grade?->name;
            $sectionName = $student->section?->name;
            $gradeSectionCode = '1A'; // Valor por defecto

            if ($gradeName && $sectionName) {
                // Mapear nombres de grados a números
                $gradeMap = [
                    'Primer' => '1',
                    'Segundo' => '2',
                    'Tercer' => '3',
                    'Cuarto' => '4',
                    'Quinto' => '5',
                    'Sexto' => '6',
                    // Agrega más grados si es necesario (por ejemplo, para Bachillerato)
                ];

                // Extraer la parte relevante del nombre del grado (ignorar "Año" u otros sufijos)
                $gradeKey = explode(' ', $gradeName)[0]; // Toma la primera palabra (ej. "Cuarto" de "Cuarto Año")
                $gradeNumber = $gradeMap[$gradeKey] ?? '1'; // Usa el número mapeado o 1 por defecto
                $gradeSectionCode = $gradeNumber . $sectionName; // Ejemplo: "4F"
            }

            // 3. Calcular el número de lista del estudiante
            $listNumber = \App\Models\Student::where([
                'type_education_id' => $student->type_education_id,
                'grade_id' => $student->grade_id,
                'section_id' => $student->section_id,
            ])
                ->whereDoesntHave('withdrawal')
                ->whereRaw('LOWER(full_name) <= LOWER(?)', [$student->full_name])
                ->orderByRaw('LOWER(full_name)')
                ->count();


            // Formatear el número de lista con dos dígitos (ej. "01", "22")
            $formattedListNumber = str_pad($listNumber, 2, '0', STR_PAD_LEFT);

            // Construir el solvencyCode (ej. "B4A-22")
            $solvencyCode = "{$educationPrefix}-{$gradeSectionCode}-{$formattedListNumber}";

            // Preparar datos del estudiante para el PDF
            $studentData = [
                'full_name' => $student["full_name"],
                'type_document_name' => $type_document_name,
                'identity_document' => $student["identity_document"],
                'grade_name' => $student->type_education?->name . " - " . $student->grade?->name . " " . $student->section?->name,
                'school_year' => "2024-2025",
            ];

            $next_school_year = '2025-2026';

            // Generar el PDF
            $pdfContent = $this->studentRepository->pdf(
                'Pdfs.SolvencyCertificate',
                [
                    'student' => $studentData,
                    'next_school_year' => $next_school_year,
                    'solvencyCode' => $solvencyCode,
                ],
                'Solvencia',
                false,
                setPaper: [0, 0, 595, 420] // Mitad de una hoja A4
            );

            if (!$pdfContent) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Error al generar el PDF',
                ], 500);
            }

            $pdfBase64 = base64_encode($pdfContent);

            return response()->json([
                'code' => 200,
                'pdf' => $pdfBase64,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
}
