<?php

namespace App\Http\Controllers;

use App\Http\Resources\Company\CompanyPwSchoolResource;
use App\Models\TeacherPlanning;
use App\Repositories\BannerRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\GradeRepository;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherComplementaryRepository;
use App\Repositories\TypeEducationRepository;

class PwController extends Controller
{
    private $companyRepository;
    private $bannerRepository;
    private $typeEducationRepository;
    private $gradeRepository;
    private $sectionRepository;
    private $studentRepository;
    private $teacherComplementaryRepository;
    private $subjectRepository;

    public function __construct(
        CompanyRepository $companyRepository,
        BannerRepository $bannerRepository,
        TypeEducationRepository $typeEducationRepository,
        GradeRepository $gradeRepository,
        SectionRepository $sectionRepository,
        StudentRepository $studentRepository,
        TeacherComplementaryRepository $teacherComplementaryRepository,
        SubjectRepository $subjectRepository,
    ) {
        $this->companyRepository = $companyRepository;
        $this->bannerRepository = $bannerRepository;
        $this->typeEducationRepository = $typeEducationRepository;
        $this->gradeRepository = $gradeRepository;
        $this->sectionRepository = $sectionRepository;
        $this->studentRepository = $studentRepository;
        $this->teacherComplementaryRepository = $teacherComplementaryRepository;
        $this->subjectRepository = $subjectRepository;
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

            $student = $this->studentRepository->find($id, ["typeEducation", "notes.subject", "grade", "section"]);
            if ($student) {
                $pdf = $this->studentRepository->pdf('Pdfs.StudentNote', [
                    'student' => $student,
                    "firma" => $firma
                ], 'Notas');

                $pdf = base64_encode($pdf);
            }

            return response()->json(['code' => 200, 'pdf' => $pdf]);
        } catch (\Throwable $th) {

            return response()->json(['code' => 500, 'message' => 'Error Al Buscar Los Datos', $th->getMessage(), $th->getLine()]);
        }
    }
}
