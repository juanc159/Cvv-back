<?php

namespace App\Exports;

use App\Models\TypeEducation;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TypeDocument;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class StudentFormatExport implements FromView, ShouldAutoSize, WithEvents
{
    use Exportable;

    protected $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    public function view(): View
    {
        return view('Exports.Student.StudentFormatExportExcel');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener el objeto hoja de cálculo
                $sheet = $event->sheet;

                // Obtener el rango de celdas con datos
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $range = 'A1:' . $highestColumn . $highestRow;

                // Establecer el filtro automático en el rango de celdas
                $sheet->setAutoFilter($range);

                // 1. Lista desplegable para "Tipo de educación" (Columna A)
                $typeEducations = TypeEducation::pluck('name')->toArray();
                $typeEducationList = '"' . implode(',', array_map('addslashes', $typeEducations)) . '"';
                $validationA = $sheet->getDelegate()->getCell('A2')->getDataValidation();
                $validationA->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationA->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationA->setAllowBlank(false);
                $validationA->setShowDropDown(true);
                $validationA->setFormula1($typeEducationList);
                $validationA->setSqref('A2:A1000');

                // 2. Lista desplegable para "Grado / Nivel" (Columna B)
                $grades = Grade::where('company_id', $this->companyId)->pluck('name')->toArray();
                $gradeList = '"' . implode(',', array_map('addslashes', $grades)) . '"';
                $validationB = $sheet->getDelegate()->getCell('B2')->getDataValidation();
                $validationB->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationB->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationB->setAllowBlank(false);
                $validationB->setShowDropDown(true);
                $validationB->setFormula1($gradeList);
                $validationB->setSqref('B2:B1000');

                // 3. Lista desplegable para "Sección" (Columna C)
                $sections = Section::pluck('name')->toArray();
                $sectionList = '"' . implode(',', array_map('addslashes', $sections)) . '"';
                $validationC = $sheet->getDelegate()->getCell('C2')->getDataValidation();
                $validationC->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationC->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationC->setAllowBlank(false);
                $validationC->setShowDropDown(true);
                $validationC->setFormula1($sectionList);
                $validationC->setSqref('C2:C1000');

                // 4. Lista desplegable para "Tipo de documento" (Columna I)
                $typeDocuments = TypeDocument::pluck('name')->toArray();
                $typeDocumentList = '"' . implode(',', array_map('addslashes', $typeDocuments)) . '"';
                $validationI = $sheet->getDelegate()->getCell('D2')->getDataValidation();
                $validationI->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationI->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationI->setAllowBlank(false);
                $validationI->setShowDropDown(true);
                $validationI->setFormula1($typeDocumentList);
                $validationI->setSqref('D2:D1000');

                // 5. Lista desplegable para "Sexo" (Columna G)
                $genderList = '"F,M"';
                $validationG = $sheet->getDelegate()->getCell('G2')->getDataValidation();
                $validationG->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationG->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationG->setAllowBlank(false);
                $validationG->setShowDropDown(true);
                $validationG->setFormula1($genderList);
                $validationG->setSqref('G2:G1000');

                // 6. Lista desplegable para "Nacionalizado" (Columna M)
                $nationalizedList = '"SÍ,NO"';
                $validationM = $sheet->getDelegate()->getCell('M2')->getDataValidation();
                $validationM->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationM->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationM->setAllowBlank(false);
                $validationM->setShowDropDown(true);
                $validationM->setFormula1($nationalizedList);
                $validationM->setSqref('M2:M1000');

                // 7. Establecer formato de texto para columnas sin listas desplegables (D, E, F, H, J, K, L)
                $textColumns = ['D', 'E', 'F', 'H', 'J', 'K', 'L'];
                foreach ($textColumns as $column) { 
                    $sheet->getStyle("{$column}2:{$column}1000")
                        ->getNumberFormat()
                        ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                }
            },
        ];
    }
}
