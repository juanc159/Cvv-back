<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Lee el archivo de promoción tal cual viene, sin interpretar encabezados.
 *
 * A propósito no usa WithHeadingRow: los archivos que manda el colegio a veces traen
 * filas de título antes de los encabezados, así que la fila de encabezados la busca
 * StudentPromotionService, que sabe reconocer las variantes de cada columna.
 */
class PromotionFileImport implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}
