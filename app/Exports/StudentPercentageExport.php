<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentPercentageExport implements FromView, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    use Exportable;

    public $headers;

    public $data;

    public $nameExcel;

    public $type_education_id;

    public function __construct($headers, $data, $nameExcel, $type_education_id = null)
    {
        $this->headers = $headers;
        $this->data = $data;
        $this->nameExcel = $nameExcel;
        $this->type_education_id = $type_education_id;
    }

    public function view(): View
    {
        return view('Exports.Student.StudentConsolidatedPercentageExport', [
            'headers' => $this->headers,
            'data' => $this->data,
            'type_education_id' => $this->type_education_id,
        ]);
    }

    public function title(): string
    {
        return $this->nameExcel;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Activar la protección general de la hoja
                // Al hacer esto, todas las celdas (que por defecto vienen bloqueadas)
                // se vuelven ineditables.
                $sheet->getProtection()->setSheet(true);

                // Opcional: Si quieres evitar que el usuario simplemente vaya a 
                // "Revisar -> Desproteger hoja" y lo edite, ponle una contraseña:
                // $sheet->getProtection()->setPassword('UnaContraseñaSegura123');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }
}
