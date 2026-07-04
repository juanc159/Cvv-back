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

class StudentExport implements FromView, ShouldAutoSize, WithEvents, WithStyles, WithTitle
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
        return view('Exports.Student.StudentConsolidatedExport', [
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

                // Obtener el rango de celdas con datos
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Proteger la hoja
                $sheet->getProtection()->setSheet(true);
                // $sheet->getProtection()->setPassword('tu_contraseña'); // Opcional

                // Desbloquear todas las celdas
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

                // Establecer el rango de bloqueo para columnas
                $protectedColumns = 'A:E';
                // Con type_education_id las columnas editables son B (PDF), C (SOLVENTE) y D (BOLETIN);
                // el bloque fijo NRO..CÉDULA llega hasta G.
                $additionalProtectedColumns = 'A:G';

                // Si $prueba tiene valor, bloquear B1 y permitir B2 en adelante
                if ($this->type_education_id) {
                    $sheet->getStyle('B1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle('C1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle('D1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle($additionalProtectedColumns)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

                    // Desbloquear los toggles editables (PDF/SOLVENTE/BOLETIN) desde la fila 2
                    if ($highestRow > 1) {
                        $sheet->getStyle('B2:B' . $highestRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                        $sheet->getStyle('C2:C' . $highestRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                        $sheet->getStyle('D2:D' . $highestRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                    }
                } else {
                    $sheet->getStyle($protectedColumns)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                }

                // Bloquear las columnas de notas en adelante (H con type_education_id, F sin él)
                $init = $this->type_education_id ? 'H1' : 'F1';
                $sheet->getStyle("$init:" . $highestColumn . '1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
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
