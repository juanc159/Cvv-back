<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConsolidatedExport implements WithMultipleSheets
{
    use Exportable;

    public $data;

    public $headers;

    public $type_education_id;

    public function __construct($data, $headers, $type_education_id = null)
    {
        $this->data = $data;
        $this->headers = $headers;
        $this->type_education_id = $type_education_id;
    }

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
            if($key!="Cuarto Grado"){
                
                $sheets[] = new StudentExport($this->headers[$key], $value, $key, $this->type_education_id);
            }
            if($key=="Cuarto Grado"){
                logMessage( "headers");
                logMessage( $this->headers[$key]);
                logMessage( "value");
                logMessage( $value);
                logMessage( "key");
                logMessage( $key);
                logMessage( "this->type_education_id");
                logMessage( $this->type_education_id);

                // $sheets[] = new StudentExport($this->headers[$key], $value, $key, $this->type_education_id);
            }
        }

        return $sheets;
    }
}
