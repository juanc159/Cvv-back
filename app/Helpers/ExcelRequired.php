<?php

namespace App\Helpers;
 
use Maatwebsite\Excel\Facades\Excel;

class ExcelRequired
{
    public static function openXls($filePath)
    {
        // Construir la ruta absoluta
        $absolutePath = storage_path('app/public/' . $filePath);

        // Verificar si el archivo existe
        if (!file_exists($absolutePath)) {
            throw new \Exception("El archivo no existe en la ruta: " . $absolutePath);
        }

        // Leer el archivo XLS usando Laravel Excel
        $data = Excel::toArray([], $absolutePath);

        // Procesar los datos obtenidos del archivo XLS
        $keys = $data[0][0]; // Los títulos se encuentran en la primera fila
        $excelData = array_slice($data[0], 1); // Eliminar la primera fila (encabezados)

        // Crear una colección con los datos del XLS, omitiendo filas completamente vacías
        $xlsCollection = collect($excelData)->filter(function ($row) {
            // Filtrar filas donde al menos una columna tenga un valor no vacío
            return collect($row)->some(function ($value) {
                return !is_null($value) && trim($value) !== '';
            });
        })->map(function ($row, $index) use ($keys) {
            $dataWithKeys = array_combine($keys, $row);
            return $dataWithKeys;
        })->values(); // Reindexar la colección para evitar huecos en los índices

        return $xlsCollection;
    }

   


}
