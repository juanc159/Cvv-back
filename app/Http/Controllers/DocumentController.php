<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Repositories\StudentRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Support\Carbon;

class DocumentController extends Controller
{
    use HttpResponseTrait;

    public function __construct(protected StudentRepository $studentRepository) {}

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
                $day = $currentDate->day;
                $month = $currentDate->monthName;
                $year = $currentDate->year;
                $formattedDate = "los $day días del mes de $month de dos mil $year";
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
            ]);

            if (!$student) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Estudiante no encontrado',
                ], 404);
            }

            // Limpiar comas del nombre completo
            $student->full_name = str_replace(',', '', $student->full_name);

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
}
