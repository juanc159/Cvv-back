<?php

namespace App\Exports;

use App\Models\PromotionBatch;
use App\Models\PromotionBatchRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Una hoja del reporte de promoción: un grupo de alumnos (los que cambian de grado, los
 * que salen de la sección, los nuevos...).
 *
 * Arriba de la tabla va el encabezado con el destino completo — período, tipo de educación,
 * grado y sección — porque estos listados se imprimen y se pasan entre personas, y una hoja
 * con solo nombres no le dice nada a quien la recibe.
 */
class PromotionBatchSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    /** Fila donde empieza la tabla; el resto de arriba es el encabezado. */
    private const HEADER_ROW = 10;

    private const COLUMNS = [
        'N°', 'Fila del archivo', 'Cédula', 'Cédula anterior', 'Nombre del alumno',
        'Venía de', 'Cómo se identificó', '¿Se aplica?', 'Observaciones',
    ];

    public function __construct(
        private PromotionBatch $batch,
        private string $action,
        private Collection $rows,
    ) {}

    public function title(): string
    {
        $label = PromotionBatchRow::actionLabels()[$this->action] ?? $this->action;

        // Excel no admite más de 31 caracteres ni ciertos signos en el nombre de la hoja.
        $title = preg_replace('/[\\\\\/\*\?\:\[\]]/', '-', $label);

        return mb_substr($title, 0, 31);
    }

    public function array(): array
    {
        $label = PromotionBatchRow::actionLabels()[$this->action] ?? $this->action;
        $matchLabels = PromotionBatchRow::matchLabels();

        $statusLabels = [
            'preview' => 'Sin aplicar (previsualización)',
            'applied' => 'Aplicado el ' . ($this->batch->applied_at ?? ''),
            'discarded' => 'Descartado',
            'reverted' => 'Deshecho',
        ];

        $rows = [
            ['PROMOCIÓN DE ALUMNOS'],
            [mb_strtoupper($label)],
            // Separador: tiene que llevar una celda, porque una fila sin celdas no se
            // escribe en el archivo y desplaza todo lo de abajo (encabezados, filtro y estilos).
            [''],
            ['Período escolar:', $this->batch->term?->name],
            ['Tipo de educación:', $this->batch->typeEducation?->name],
            ['Grado destino:', $this->batch->grade?->name],
            ['Sección destino:', $this->batch->section?->name],
            ['Archivo cargado:', $this->batch->file_name, '', 'Estado:', $statusLabels[$this->batch->status] ?? $this->batch->status],
            ['Total en este listado:', $this->rows->count(), '', 'Generado:', now()->format('d/m/Y H:i')],
            self::COLUMNS,
        ];

        $number = 1;

        foreach ($this->rows as $row) {
            $rows[] = [
                $number++,
                $row->row_number ?? '—',
                $row->identity_document_normalized ?: $row->identity_document_raw,
                $row->previous_identity_document ?: '',
                $row->full_name_raw,
                $row->previous_grade_name
                    ? trim($row->previous_grade_name . ' ' . ($row->previous_section_name ?? ''))
                    : '—',
                $matchLabels[$row->match_type] ?? '—',
                $this->appliedLabel($row),
                implode(' ', $row->messages ?? []),
            ];
        }

        return $rows;
    }

    /**
     * Qué pasó (o qué va a pasar) con la fila, según el estado del lote.
     */
    private function appliedLabel(PromotionBatchRow $row): string
    {
        if ($row->action === PromotionBatchRow::ACTION_ERROR) {
            return 'No';
        }

        if ($this->batch->status === 'applied') {
            return $row->is_applied ? 'Sí, aplicado' : 'No se aplicó';
        }

        if ($row->needs_review && ! $row->is_approved) {
            return 'Pendiente de confirmar';
        }

        return $row->is_approved ? 'Sí' : 'No';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColumn = 'I';
                $lastRow = $sheet->getHighestRow();
                $headerRow = self::HEADER_ROW;

                // Título del reporte.
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A1:A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Etiquetas del bloque de contexto.
                $sheet->getStyle('A4:A9')->getFont()->setBold(true);
                $sheet->getStyle('D8:D9')->getFont()->setBold(true);

                // Encabezados de la tabla.
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8EAF6');

                if ($lastRow > $headerRow) {
                    $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    // Filtro sobre la tabla, que es lo que se usa para revisar estos listados.
                    $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
                }

                // Las observaciones son largas: se ajustan en varias líneas.
                $sheet->getStyle("I{$headerRow}:I{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('I')->setAutoSize(false);
                $sheet->getColumnDimension('I')->setWidth(60);

                $sheet->freezePane("A" . ($headerRow + 1));
            },
        ];
    }
}
