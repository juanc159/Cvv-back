<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConsolidatedExport implements WithMultipleSheets
{
    use Exportable;

    public $data;
    public $headers;

    public function __construct($data,$headers)
    {
        $this->data = $data;
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Agrupar por 'grade' y ordenar todos los estudiantes por 'section' alfabéticamente
        $groupedStudents = collect($this->data)
            ->groupBy('grade')
            ->map(function ($gradeGroup) {
                // Ordenar estudiantes por section alfabéticamente
                return $gradeGroup->sortBy('section');
            });

        // Si deseas convertirlo a un array
        $groupedStudentsArray = $groupedStudents->toArray();

        foreach ($groupedStudentsArray as $key => $value) {
            $sheets[] = new StudentExport($this->headers[$key],$value,$key);
        }

        return $sheets;
    }
}
