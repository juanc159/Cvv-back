<?php

namespace App\Http\Controllers;

use App\Repositories\BannerRepository;
use App\Repositories\GradeRepository;
use App\Repositories\RoleRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected RoleRepository $roleRepository,
        protected BannerRepository $bannerRepository,
        protected SubjectRepository $subjectRepository,
        protected GradeRepository $gradeRepository,
        protected ServiceRepository $serviceRepository,
        protected StudentRepository $studentRepository,
        protected TeacherRepository $teacherRepository,
    ) {}

    public function countAllData(Request $request)
    {
        try {
            $request["is_active"] = true;

            $userCount = $this->userRepository->countData($request->all());
            $roleCount = $this->roleRepository->countData($request->all());
            $bannerCount = $this->bannerRepository->countData($request->all());
            $subjectCount = $this->subjectRepository->countData($request->all());
            $gradeCount = $this->gradeRepository->countData($request->all());
            $serviceCount = $this->serviceRepository->countData($request->all());
            $studentCount = $this->studentRepository->countData($request->all());
            $teacherCount = $this->teacherRepository->countData($request->all());

            return response()->json([
                'code' => 200,
                "userCount" => $userCount,
                "roleCount" => $roleCount,
                "bannerCount" => $bannerCount,
                "subjectCount" => $subjectCount,
                "gradeCount" => $gradeCount,
                "serviceCount" => $serviceCount,
                "studentCount" => $studentCount,
                "teacherCount" => $teacherCount,
            ]);
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function studentByTypeEducation(Request $request)
    {
        try {
            $students = $this->studentRepository->getCountByTypeEducation([
                "company_id" => $request["company_id"],
            ]);

            $dataSeries = [];
            $labels = [];

            // Organizar los datos en los arrays
            foreach ($students as $key => $value) {
                $type_education_name = $value["name"];
                $color = generatePastelColor();

                // Agregar el nombre del tipo de educación a los labels si no está ya
                if (!in_array($type_education_name, $labels)) {
                    $labels[] = $type_education_name;

                    // Inicializar el dataset para este tipo de educación
                    $dataSeries[] = [
                        "label" => $type_education_name,
                        "data" => array_fill(0, count($labels), 0), // Inicializar con ceros
                        "backgroundColor" => $color, // Color de fondo para la gráfica
                    ];
                }

                // Obtener el índice del dataset correspondiente al tipo de educación
                $index = array_search($type_education_name, $labels);

                // Actualizar el total solo en el índice correspondiente
                $dataSeries[$index]["data"][$key] = $value["total"];
            }

            return response()->json([
                'code' => 200,
                'labels' => $labels,
                'datasets' => $dataSeries,
            ]);
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }

    public function studentByPhotoStatus(Request $request)
    {
        try {
            // Contamos los estudiantes con foto y sin foto
            $studentsWithPhoto = $this->studentRepository->getCountByPhotoStatus([
                "company_id" => $request["company_id"],
                "has_photo" => true,
            ]);

            $studentsWithoutPhoto = $this->studentRepository->getCountByPhotoStatus([
                "company_id" => $request["company_id"],
                "has_photo" => false,
            ]);

            // Preparamos los datos para la gráfica de torta
            $labels = ['Con Foto', 'Sin Foto'];
            $dataSeries = [
                [
                    'data' => [$studentsWithPhoto, $studentsWithoutPhoto],  // Los conteos
                    'backgroundColor' => ['#36A2EB', '#FF6384'],  // Colores del gráfico
                    'hoverBackgroundColor' => ['#36A2EB', '#FF6384'],
                ]
            ];

            return response()->json([
                'code' => 200,
                'labels' => $labels,
                'datasets' => $dataSeries,
            ]);
        } catch (Throwable $th) {
            return response()->json(['code' => 500, 'message' => $th->getMessage()]);
        }
    }
}
