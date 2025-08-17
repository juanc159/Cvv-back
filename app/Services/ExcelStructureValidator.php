<?php

namespace App\Services;

use App\Repositories\TeacherRepository;
use App\Repositories\TypeEducationRepository;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExcelStructureValidator
{
    protected $teacherRepository;
    protected $typeEducationRepository;

    public function __construct(TeacherRepository $teacherRepository, TypeEducationRepository $typeEducationRepository)
    {
        $this->teacherRepository = $teacherRepository;
        $this->typeEducationRepository = $typeEducationRepository;
    }

    public function validate($filePath, $teacherId = null, $typeEducationId = null, $companyId = 1)
    {
        $expectedHeaders = ['NRO', 'PDF', 'SOLVENTE', 'AÑO', 'SECCIÓN', 'CÉDULA', 'NOMBRES Y APELLIDOS ESTUDIANTE'];

        try {
            $sheets = Excel::toArray([], $filePath, null, \Maatwebsite\Excel\Excel::XLSX);

            $validationResults = [];
            $operationFailed = false;

            foreach ($sheets as $index => $sheet) {
                $sheetName = 'Hoja ' . ($index + 1);
                // Log::info('Processing sheet: ' . $sheetName);
                // Log::info('First row of sheet: ' . json_encode($sheet[0]));

                $headers = array_map('strtoupper', $sheet[0]);
                $headers = array_slice($headers, 0, count($expectedHeaders));

                $errors = [];
                // Validación SOLO de encabezados fijos
                for ($i = 0; $i < count($expectedHeaders); $i++) {
                    if (!isset($headers[$i]) || $headers[$i] !== $expectedHeaders[$i]) {
                        if (!isset($headers[$i])) {
                            $errors[] = "Falta el encabezado esperado '{$expectedHeaders[$i]}' en la posición " . ($i + 1);
                        } else {
                            $errors[] = "El encabezado '{$headers[$i]}' en la posición " . ($i + 1) . " no coincide con '{$expectedHeaders[$i]}' esperado";
                        }
                    }
                }

                // Se OMITE completamente la validación de materias
                // Solo se validan los encabezados básicos

                if (!empty($errors)) {
                    $operationFailed = true;
                }

                $validationResults[$sheetName] = [
                    'valid' => empty($errors),
                    'errors' => $errors
                ];
            }

            return [
                'operation_failed' => $operationFailed,
                'data' => $validationResults
            ];
        } catch (\Exception $e) {
            throw new \Exception("Error al procesar el archivo: " . $e->getMessage());
        }
    }
}