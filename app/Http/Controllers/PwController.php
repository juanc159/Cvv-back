<?php

namespace App\Http\Controllers;

use App\Http\Resources\Company\CompanyPwSchoolResource;
use App\Models\Banner;
use App\Models\Company;
use App\Models\CompanyDetail;
use App\Models\Service;
use App\Models\Teacher;
use App\Models\TeacherPlanning;
use App\Repositories\BannerRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\GradeRepository;
use App\Repositories\SectionRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TeacherPlanningRepository;
use App\Repositories\TypeEducationRepository;
use Carbon\Carbon;

class PwController extends Controller
{

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
        $banners = $this->bannerRepository->list(['typeData' => 'all', 'company_id' => null], select: ['id', 'path']);
        $companies = $this->companyRepository->list(['typeData' => 'all'], select: ['id', 'name', 'image_principal']);

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

        //BACHILLERATO
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

                $files = TeacherPlanning::where(function ($query) use ($value, $subject) {
                    $query->where('teacher_id', $value->teacher_id);
                    $query->where('grade_id', $value->grade_id);
                    $query->where('section_id', $value->section_id);
                    $query->where('subject_id', $subject->id);
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
        ], ["notes"])->map(function ($value) {
            return [
                "id" => $value->id,
                "full_name" => $value->full_name,
                "identity_document" => $value->identity_document,
                "pdf" => $value->pdf,
                "photo" => $value->photo,
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
            $rutaImagen = public_path('/img/firma.png');
            if (file_exists($rutaImagen)) {
                $contenidoImagen = file_get_contents($rutaImagen);
                $firma = base64_encode($contenidoImagen);
            }

            $fecha = Carbon::now();
            $fecha->setLocale('es');

            $student = $this->studentRepository->find($id, ["typeEducation", "notes.subject", "grade", "section"]);
            if ($student) {
                $pdf = $this->studentRepository->pdf('Pdfs.StudentNote', [
                    'student' => $student,
                    "date" => $fecha->translatedFormat('l, j \\de F \\de Y'),
                ], 'Notas', false);

                $pdf = base64_encode($pdf);
            }

            return response()->json(['code' => 200, 'pdf' => $pdf]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }
    public function socialNetworks($company_id)
    {
        try {
            $social_networks = CompanyDetail::where("company_id", $company_id)->whereIn("type_detail_id", [1, 2, 3, 4, 5])->get()->map(function ($value) {
                return [
                    'type_detail_name' => $value->typeDetail?->name,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => $value->content,
                ];
            });

            return response()->json(['code' => 200, 'social_networks' => $social_networks]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }
    public function banners($company_id)
    {
        try {
            $banners = Banner::select("path")->where("company_id", $company_id)->where("state", 1)->get();

            return response()->json(['code' => 200, 'banners' => $banners]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function teachers($company_id)
    {
        try {
            $typeEducations = $this->typeEducationRepository->selectList();


            $teachers = Teacher::where("company_id", $company_id)->where("state", 1)->orderBy('order')->get()->map(function ($value) {
                $grade_name = '';
                $section_name = '';

                $info = $value->complementaries->first();
                if ($info) {
                    $grade_name = $info->grade?->name;
                    $section_name = $info->section?->name;
                }

                return [
                    // 'info' => $info,
                    'id' => $value->id,
                    'fullName' => $value->name . ' ' . $value->last_name,
                    'photo' => $value->photo,
                    'type_education_id' => $value->type_education_id,
                    'type_education_name' => $value->typeEducation?->name,
                    'email' => $value->email,
                    'phone' => $value->phone,
                    'jobPosition' => $value->jobPosition?->name,
                    'backgroundColor' => generarColorPastelAleatorio(70),
                    'grade_name' => $grade_name,
                    'section_name' => $section_name,
                ];
            })->groupBy('type_education_name')->toArray();

            return response()->json(['code' => 200, 'teachers' => $teachers, 'typeEducations' => $typeEducations]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }

    public function contactData($company_id)
    {
        try {
            $company = Company::with([
                "details" => function ($q) {
                    $q->whereIn("type_detail_id", [6, 7, 8]);
                }
            ])->find($company_id);

            $contactData["details"] = $company->details?->map(function ($value) {
                return [
                    'type_detail_name' => $value->typeDetail?->name,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => $value->content,
                ];
            });

            $contactData["iframeGoogleMap"] = $company->iframeGoogleMap;


            return response()->json(['code' => 200, 'contactData' => $contactData]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }
    public function services($company_id)
    {
        try {
            $company = Company::with([
                "services" => function ($q) {
                    $q->where("state", 1);
                }
            ])->find($company_id);

            $services = $company->services?->map(function ($value) {
                return [
                    'id' => $value->id,
                    'title' => $value->title,
                    'image' => $value->image,
                    'html' => $value->html,
                ];
            }) ?? [];

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


    public function materiaPendiente()
    {
        try {
            $plannings = TeacherPlanning::with([
                "subject",
                "grade",
                "section"
            ])->whereHas("teacher", function ($q) {
                $q->where("name", "Materia");
                $q->where("last_name", "Pendiente");
            })->get()->groupBy(["grade.name", "section.name", "subject.name"]);



            return response()->json(['code' => 200, 'plannings' => $plannings]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }
}
