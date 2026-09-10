<?php

namespace App\Exports;

use App\Models\PromotionBatch;
use App\Models\PromotionBatchRow;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte en Excel de un lote de promoción.
 *
 * Sin filtro genera una hoja por cada grupo del análisis (cambian de grado, alumnos nuevos,
 * salen de la sección...). Con un grupo indicado devuelve solo ese, que es lo que se usa
 * desde el botón de descarga de cada listado en pantalla.
 */
class PromotionBatchExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private PromotionBatch $batch,
        private ?string $action = null,
    ) {}

    public function sheets(): array
    {
        $grouped = $this->batch->rows->groupBy('action');
        $sheets = [];

        foreach (PromotionBatchRow::actionOrder() as $action) {
            if ($this->action !== null && $this->action !== $action) {
                continue;
            }

            $rows = $grouped->get($action);

            // Sin filtro se omiten los grupos vacíos; con filtro se genera igual la hoja,
            // para que el usuario reciba un archivo y no un error.
            if (($rows === null || $rows->isEmpty()) && $this->action === null) {
                continue;
            }

            $sheets[] = new PromotionBatchSheet($this->batch, $action, $rows ?? collect());
        }

        return $sheets;
    }
}
