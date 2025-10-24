<?php

namespace App\Helpers;

use App\Helpers\ErrorCollector; 
use App\Helpers\ErrorCodes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExcelValidator
{

    public static function validateAll(string $batchId, $xlsCollection, array $requiredColumns)
    {
        $errors = false;

        //Validando que el excel no este vacio
        if ($xlsCollection->isEmpty()) {

            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('STUDENT_EXCEL_001'),
                ErrorCodes::STUDENT_EXCEL_001['code'],
                null,
                ''
            );
            $errors = true;
        }

        // Empieza la validación de columnas
        // Normaliza y verifica columnas requeridas
        $normalize = fn($s) => Str::of($s)->upper()->replace(' ', '')->toString();
        // Obtener las claves del primer elemento y normalizarlas
        $headers = collect(array_keys($xlsCollection->first()))->map($normalize);
        // Comparar con las columnas requeridas
        $missing = collect($requiredColumns)->diff($headers);
        Log::info("missing" ,["missing"=>$missing, "required"=>$requiredColumns, "headers"=>$headers]);
        if ($missing->isNotEmpty()) {
            // Convierte claves faltantes a nombres legibles
            $cols = $missing->map(fn($k) => $k)->values()->all();

            // Formatea: "a, b y c"
            $last = array_pop($cols);
            $colsStr = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('STUDENT_EXCEL_002', $colsStr),
                ErrorCodes::STUDENT_EXCEL_002['code'],
                null,
                ''
            );
            $errors = true;
        }
        return $errors;
    }
}
