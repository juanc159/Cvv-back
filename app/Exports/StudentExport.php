<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class StudentExport implements FromView, WithEvents, ShouldAutoSize, WithTitle
{
    use Exportable;

    public $headers;
    public $data;
    public $nameExcel;

    public function __construct($headers, $data, $nameExcel)
    {
        $this->headers = $headers;
        $this->data = $data;
        $this->nameExcel = $nameExcel;
    }

    public function view(): View
    {

        return view('Exports.Student.StudentConsolidatedExport', [
            'headers' => $this->headers,
            'data' => $this->data,
        ]);
    }


    /**
     * @return string
     */
    public function title(): string
    {
        return $this->nameExcel;
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
            },
        ];
    }
}
