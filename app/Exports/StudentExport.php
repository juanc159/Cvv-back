<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;

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

    public function title(): string
    {
        return $this->nameExcel;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Obtener el rango de celdas con datos
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Proteger la hoja
                $sheet->getProtection()->setSheet(true);
                // $sheet->getProtection()->setPassword('tu_contraseña'); // Opcional

                // Desbloquear todas las celdas
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

                // Bloquear solo las columnas A a E y la primera fila de la F en adelante
                $sheet->getStyle('A:E')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                $sheet->getStyle('F1:' . $highestColumn . '1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
            },
        ];
    }
}
