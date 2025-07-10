<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Term;
use App\Repositories\GradeRepository;
use App\Repositories\SectionRepository;
use App\Repositories\StudentRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;


class DocumentController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected StudentRepository $studentRepository,
        protected GradeRepository $gradeRepository,
        protected SectionRepository $sectionRepository,

    ) {}

    /**
     * Muestra los datos de un estudiante.
     *
     * @param string $id
     */
    public function show(string $id)
    {
        return $this->runTransaction(function () use ($id) {
            $student = $this->studentRepository->find($id, with: [
                'grade:id,name',
                'section:id,name',
                'type_education:id,name',
            ], select: [
                'id',
                'full_name',
                'grade_id',
                'section_id',
                'photo',
                'type_education_id',
            ]);

            return [
                'code' => 200,
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'grade_name' => $student->grade?->name,
                    'section_name' => $student->section?->name,
                    'type_education_name' => $student->type_education?->name,
                    'photo' => $student->photo,
                ],
            ];
        });
    }

    /**
     * Genera un certificado de estudios.
     *
     * @param string $id
     */
    public function certificate(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.StudyCertificate');
    }

    /**
     * Genera un certificado de finalización.
     *
     * @param string $id
     */
    public function certificateCompletion(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateCompletion');
    }

    /**
     * Genera una constancia de no tener beca.
     *
     * @param string $id
     */
    public function proofOfNotHavingScholarship(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.ProofOfNotHavingScholarship');
    }

    /**
     * Genera un certificado de aprobación.
     *
     * @param string $id
     */
    public function certificateApproval(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateApproval');
    }

    /**
     * Genera un certificado de buena conducta.
     *
     * @param string $id
     */
    public function certificateOfGoodConduct(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateOfGoodConduct');
    }

    /**
     * Genera un certificado de matrícula.
     *
     * @param string $id
     */
    public function certificateOfEnrollment(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateOfEnrollment');
    }

    /**
     * Genera una constancia de transportista.
     *
     * @param string $id
     */
    public function certificateTransporter(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateTransporter', request()->get('additionalInfo'));
    }

    /**
     * Genera una constancia de inscripción por monto.
     *
     * @param string $id
     */
    public function certificateEnrollmentAmount(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateEnrollmentAmount', request()->get('additionalInfo'));
    }

    /**
     * Genera una constancia de retiro.
     *
     * @param string $id
     */
    public function certificateWithdrawal(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.CertificateWithdrawal', request()->get('additionalInfo'));
    }

    /**
     * Genera un permiso de ausencia escolar.
     *
     * @param string $id
     */
    public function absencePermission(string $id)
    {
        return $this->generateCertificate($id, 'Pdfs.AbsencePermission', request()->get('additionalInfo'));
    }

    /**
     * Método común para generar certificados en PDF.
     *
     * @param string $id
     * @param string $view
     * @param array|null $additionalInfo
     */
    private function generateCertificate(string $id, string $view, ?array $additionalInfo = null)
    {
        return $this->runTransaction(function () use ($id, $view, $additionalInfo) {
            // Obtener el término activo
            $term = Term::where('is_active', 1)->first();

            // Formatear la fecha actual en español
            $currentDate = Carbon::now();
            if ($currentDate) {
                $currentDate->setLocale('es');
                $day = str_pad($currentDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day
                $month = $currentDate->monthName;
                $year = $currentDate->year;
                $formattedDate = "los $day días del mes de $month de $year";
            } else {
                $day = $month = $year = '';
                $formattedDate = '';
            }

            // Consultar el estudiante con sus relaciones
            $student = $this->studentRepository->find($id, [
                'grade',
                'section',
                'type_education',
                'company',
                'type_document',
            ]);

            if (!$student) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Estudiante no encontrado',
                ], 404);
            }

            // Limpiar comas del nombre completo
            $student->full_name = str_replace(',', '', $student->full_name);

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
            $student->type_document_name = $type_document_name;

            // Preparar datos para el PDF, incluyendo información adicional si existe
            $pdfData = [
                'student' => $student,
                'date' => $formattedDate,
                'term' => $term,
            ];

            if ($additionalInfo) {
                $pdfData['additionalInfo'] = $additionalInfo;
            }

            // Generar el PDF
            $pdfContent = $this->studentRepository->pdf(
                $view,
                $pdfData,
                'Constancia_de_Estudios_' . $student->full_name,
                true // Forzar descarga
            );

            if (!$pdfContent) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Error al generar el PDF',
                ], 500);
            }

            return [
                'code' => 200,
                'pdf' => base64_encode($pdfContent),
            ];
        });
    }

    public function masiveCertificatesData()
    {
        return $this->execute(function () {
            $sections = $this->sectionRepository->selectList();
            $greades = $this->gradeRepository->selectList();


            return [
                "code" => 200,
                "sections" => $sections,
                "greades" => $greades,

            ];
        });
    }


    /**
     * Genera InitialEducation certificate.
     */
    public function prosecutionInitialEducation()
    {
        return $this->runTransaction(function () {
            // Array de meses en español
            $spanishMonths = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre',
            ];

            // Array para mapear niveles a números romanos
            $levelToRoman = [
                'Primer Nivel' => 'I',
                'Segundo Nivel' => 'II'
            ];

            // Obtener el término activo
            $term = Term::where('is_active', 1)->first();

            $request = request()->all();

            $grade = $this->gradeRepository->find($request['grade_id']);
            $company_id = $request['company_id'];

            // Formatear la fecha actual en español
            $currentDate = Carbon::now();
            if ($currentDate) {
                $currentDate->setLocale('es');
                $day = str_pad($currentDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day
                $month = $currentDate->monthName;
                $year = $currentDate->year;
                $formattedDate = "los <strong>$day</strong> días del mes de <strong>$month</strong> de <strong>$year</strong>";
            } else {
                $day = $month = $year = '';
                $formattedDate = '';
            }

            $students = $this->studentRepository->getStudentsByGradeAndCompany($grade->id, $company_id, $request);

            if (count($students) === 0) {
                return [
                    'code' => 400,
                    'message' => "No se encontraron estudiantes.",
                ];
            }
            // Procesar el nombre de cada estudiante: eliminar comas y convertir a camelCase por palabra
            $students = $students->map(function ($student) use ($spanishMonths, $levelToRoman) {
                // Procesar full_name: eliminar comas y convertir a camelCase por palabra
                $fullName = $student['full_name'];
                $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
                $fullName = ucwords(strtolower($fullName));
                $student['full_name'] = $fullName;

                // Procesar identity_document: eliminar comas, aplicar camelCase y agregar prefijo basado en country_id
                $identityDocument = $student['identity_document'];
                $prefix = $student['country_id'] == $student->company->country_id ? 'V-' : 'E-';
                $student['identity_document'] = $prefix . $identityDocument;

                // Procesar birth_place: construir dinámicamente con city y state
                $cityName = $student->city->name ?? 'NO POSSEE'; // Valor por defecto si no existe
                $stateName = $student->state->name ?? 'NO POSSEE'; // Valor por defecto si no existe
                $birthPlace = "Municipio <strong>$cityName</strong>, del Estado <strong>$stateName</strong>";
                $student['birth_place'] = $birthPlace;

                // Procesar birthday: formatear como "01 de Marzo del 2020" usando el array de meses
                if (!empty($student['birthday'])) {
                    $birthDate = Carbon::parse($student['birthday']);
                    $day = str_pad($birthDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day
                    $month = $spanishMonths[$birthDate->month];
                    $year = $birthDate->year;
                    $student['birthday'] = "$day de $month del $year";
                } else {
                    $student['birthday'] = 'Fecha no disponible'; // Valor por defecto si no hay fecha
                }

                // Procesar numGroup: mapear el nivel a número romano
                $gradeName = $student['grade']['name'] ?? 'NO POSEE'; // Valor por defecto si no existe
                $student['numGroup'] = $levelToRoman[$gradeName] ?? 'NO POSEE'; // Valor por defecto si el nivel no está en el mapeo

                return $student;
            });

            // Preparar datos para el PDF, incluyendo información adicional si existe
            $pdfData = [
                'date' => $formattedDate,
                'grade' => $grade,
                'students' => $students,
                'term' => $term,
            ];

            // Generar el PDF
            $pdfContent = $this->studentRepository->pdf(
                "Pdfs.CertificateProsecution",
                $pdfData,
                'Constancia_de_Prosecusión_' . $grade->name,
                true // Forzar descarga
            );

            if (!$pdfContent) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Error al generar el PDF',
                ], 500);
            }

            return [
                'code' => 200,
                'pdf' => base64_encode($pdfContent),
            ];
        });
    }

    /**
     * Certificado de educación Inicial
     */
    public function certificateInitialEducation()
    {
        return $this->runTransaction(function () {
            // Array de meses en español
            $spanishMonths = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre',
            ];

            // Obtener el término activo
            $term = Term::where('is_active', 1)->first();

            $request = request()->all();

            $grade = $this->gradeRepository->find(3);
            $company_id = $request['company_id'];

            // Formatear la fecha actual en español
            $currentDate = Carbon::now();
            if ($currentDate) {
                $currentDate->setLocale('es');
                $day = str_pad($currentDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day
                $month = $currentDate->monthName;
                $year = $currentDate->year;
                $formattedDate = "los <strong>$day</strong> días del mes de <strong>$month</strong> de <strong>$year</strong>";
            } else {
                $day = $month = $year = '';
                $formattedDate = '';
            }

            $students = $this->studentRepository->getStudentsByGradeAndCompany($grade->id, $company_id, $request);

            if (count($students) === 0) {
                return [
                    'code' => 400,
                    'message' => "No se encontraron estudiantes.",
                ];
            }

            // Procesar el nombre de cada estudiante: eliminar comas y convertir a camelCase por palabra
            $students = $students->map(function ($student) use ($spanishMonths) {
                // Procesar full_name: eliminar comas y convertir a camelCase por palabra
                $fullName = $student['full_name'];
                $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
                $fullName = ucwords(strtolower($fullName));
                $student['full_name'] = $fullName;

                // Procesar identity_document: eliminar comas, aplicar camelCase y agregar prefijo basado en country_id
                $identityDocument = $student['identity_document'];
                $prefix = $student['country_id'] == $student->company->country_id ? 'V-' : 'E-';
                $student['identity_document'] = $prefix . $identityDocument;

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
                $student["type_document_name"] = $type_document_name;



                // Procesar birth_place: construir dinámicamente con city y state
                $cityName = $student->city->name ?? 'NO POSSEE'; // Valor por defecto si no existe
                $stateName = $student->state->name ?? 'NO POSSEE'; // Valor por defecto si no existe
                $birthPlace = "Municipio <strong>$cityName</strong>, del Estado <strong>$stateName</strong>";
                $student['birth_place'] = $birthPlace;


                // Procesar birthday: formatear como "12 de Marzo del 2020" usando el array de meses
                if (!empty($student['birthday'])) {
                    $birthDate = Carbon::parse($student['birthday']);
                    $day = str_pad($birthDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day

                    $month = $spanishMonths[$birthDate->month];
                    $year = $birthDate->year;
                    $student['birthday'] = "$day de $month del $year";
                } else {
                    $student['birthday'] = 'Fecha no disponible'; // Valor por defecto si no hay fecha
                }

                return $student;
            });

            // Preparar datos para el PDF, incluyendo información adicional si existe
            $pdfData = [
                'date' => $formattedDate,
                'grade' => $grade,
                'students' => $students,
                'term' => $term,

            ];

            // Generar el PDF
            $pdfContent = $this->studentRepository->pdf(
                "Pdfs.CertificateInitialEducation",
                $pdfData,
                'Certificado_Educacion_Inicial' . $grade->name,
                true // Forzar descarga
            );

            if (!$pdfContent) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Error al generar el PDF',
                ], 500);
            }

            return [
                'code' => 200,
                'pdf' => base64_encode($pdfContent),
            ];
        });
    }

    /**
     * Genera PrimaryEducation certificate.
     */
    public function prosecutionPrimaryEducation()
    {
        return $this->runTransaction(function () {
            // Array de meses en español
            $spanishMonths = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre',
            ];

            $request = request()->all();

            $grade = $this->gradeRepository->find($request['grade_id']);
            $company_id = $request['company_id'];

            // Obtener el término activo
            $term = Term::where('is_active', 1)->first();

            // Obtener todos los grados de la compañía, ordenados por 'order', con su tipo de educación
            $grades = Grade::where("company_id", $company_id)
                ->with('typeEducation') // Cargar la relación typeEducation
                ->orderBy('order', 'asc')
                ->get();

            // Determinar el grado siguiente
            $currentOrder = $grade->order;
            $nextGrade = $grades->firstWhere('order', $currentOrder + 1);

            $nextGradeNameWithType = 'NO POSSE';
            $titlePdf = 'CONSTANCIA DE PROSECUCIÓN';
            $subTitlePdf = 'EN EL NIVEL DE EDUCACIÓN PRIMARIA';
            $signatureImg = true;
            $pdfView = "Pdfs.CertificateProsecutionPrimary";


            // Si el grado actual es 6to Grado, forzamos "1er Año" como siguiente
            if ($currentOrder === 9) {
                $nextGradeNameWithType = '1er Año del Nivel de Educación Media';
                $titlePdf = 'CERTIFICADO';
                $subTitlePdf = 'DE EDUCACIÓN PRIMARIA';
                $signatureImg = false;
                $pdfView = "Pdfs.CertificateProsecutionPrimary6to";
            } else {
                // Si hay un grado siguiente, usamos su nombre y tipo de educación
                if ($nextGrade) {
                    $nextGradeName = $nextGrade->name;
                    $nextEducationType = $nextGrade->educationType->name ?? 'Educación Primaria';
                    $nextGradeNameWithType = "$nextGradeName de $nextEducationType";
                }
            }


            // Formatear la fecha actual en español
            $currentDate = Carbon::now();
            if ($currentDate) {
                $currentDate->setLocale('es');
                $day = str_pad($currentDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day
                $month = $spanishMonths[$currentDate->month];
                $year = $currentDate->year;
                $formattedDate = "los <strong>$day</strong> días del mes de <strong>$month</strong> de <strong>$year</strong>";
            } else {
                $formattedDate = '';
            }

            $students = $this->studentRepository->getStudentsByGradeAndCompany($grade->id, $company_id, $request);

            if (count($students) === 0) {
                return [
                    'code' => 400,
                    'message' => "No se encontraron estudiantes.",
                ];
            }

            // Procesar el nombre de cada estudiante
            $students = $students->map(function ($student) use ($spanishMonths, $nextGradeNameWithType) {
                // Procesar full_name: eliminar comas y convertir a camelCase por palabra
                $fullName = $student['full_name'];
                $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
                $fullName = ucwords(strtolower($fullName));
                $student['full_name'] = $fullName;

                // Procesar identity_document: eliminar comas, aplicar camelCase y agregar prefijo basado en country_id
                $identityDocument = $student['identity_document'];
                $prefix = $student['country_id'] == $student->company->country_id ? 'V-' : 'E-';
                $student['identity_document'] = $prefix . $identityDocument;

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
                $student["type_document_name"] = $type_document_name;

                // Procesar birth_place: construir dinámicamente con city y state
                $cityName = $student->city->name ?? 'NO POSSEE';
                $stateName = $student->state->name ?? 'NO POSSEE';
                $birthPlace = "Municipio <strong>$cityName</strong>, del Estado <strong>$stateName</strong>";
                $student['birth_place'] = $birthPlace;

                // Procesar birthday: formatear como "12 de Marzo del 2020" usando el array de meses
                if (!empty($student['birthday'])) {
                    $birthDate = Carbon::parse($student['birthday']);
                    $day = str_pad($birthDate->day, 2, '0', STR_PAD_LEFT); // Ensure two digits for day
                    $month = $spanishMonths[$birthDate->month];
                    $year = $birthDate->year;
                    $student['birthday'] = "$day de $month del $year";
                } else {
                    $student['birthday'] = 'Fecha no disponible';
                }

                // Agregar el grado actual
                if ($student['grade']['name'] == 'Sexto Grado') {
                    $student['grade']['name'] = "6to Grado";
                } else {
                    $student['grade']['name'] = $student['grade']['name'] . " de Educación Primaria";
                }

                $student['currentGrade'] = $student['grade']['name'] ?? 'NO POSEE';

                // Agregar el grado siguiente con el tipo de educación
                $student['nextGrade'] = $nextGradeNameWithType;

                $student['literal'] = !empty($student['literal']) ?  $student['literal'] :  "NO POSSE";

                return $student;
            });

            // Preparar datos para el PDF
            $pdfData = [
                'date' => $formattedDate,
                'grade' => $grade,
                'students' => $students,
                'term' => $term,
                'titlePdf' => $titlePdf,
                'subTitlePdf' => $subTitlePdf,
                'signatureImg' => $signatureImg,
            ];

            // Generar el PDF
            $pdfContent = $this->studentRepository->pdf(
                $pdfView,
                $pdfData,
                'Constancia_de_Prosecución_' . $grade->name,
                true // Forzar descarga
            );

            if (!$pdfContent) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Error al generar el PDF',
                ], 500);
            }

            return [
                'code' => 200,
                'pdf' => base64_encode($pdfContent),
            ];
        });
    }

    public function searchStudentFor(Request $request)
    {
        return $this->execute(function () use ($request) {

            $grade_id = $request->get('grade_id');
            $company_id = $request->get('company_id');
            $students = $this->studentRepository->getStudentsByGradeAndCompany($grade_id, $company_id, $request, ["id", "full_name", "identity_document", "literal"]);

            if (count($students) === 0) {
                return [
                    'code' => 400,
                    'message' => "No se encontraron estudiantes.",
                ];
            }
            return [
                "code" => 200,
                "students" => $students,
            ];
        });
    }
}
