<?php

namespace App\Helpers;

class ErrorCodes
{
    // Errores de estructura en EXCEL para estudiantes
    const STUDENT_EXCEL_001 = ['code' => 'STUDENT_EXCEL_001', 'message' => 'El archivo no contiene filas.'];
    const STUDENT_EXCEL_002 = ['code' => 'STUDENT_EXCEL_002', 'message' => 'Estructura inválida en el Excel. Faltan columnas requeridas: %s.'];
    
    // Errores de validación de datos en EXCEL para estudiantes
    const STUDENT_EXCEL_003 = ['code' => 'STUDENT_EXCEL_003', 'message' => 'El campo %s es requerido y no puede estar vacío.'];
    const STUDENT_EXCEL_004 = ['code' => 'STUDENT_EXCEL_004', 'message' => 'El valor %s en type_education_id no existe.'];
    const STUDENT_EXCEL_005 = ['code' => 'STUDENT_EXCEL_005', 'message' => 'El valor %s en grade_id no existe.'];
    const STUDENT_EXCEL_006 = ['code' => 'STUDENT_EXCEL_006', 'message' => 'El valor %s en section_id no existe.'];
    const STUDENT_EXCEL_007 = ['code' => 'STUDENT_EXCEL_007', 'message' => 'El valor %s en type_document_id no existe.'];
    const STUDENT_EXCEL_008 = ['code' => 'STUDENT_EXCEL_008', 'message' => 'El valor %s en gender es inválido (debe ser F o M).'];
    const STUDENT_EXCEL_009 = ['code' => 'STUDENT_EXCEL_009', 'message' => 'El valor %s en birthday es inválido.'];
    const STUDENT_EXCEL_010 = ['code' => 'STUDENT_EXCEL_010', 'message' => 'El valor %s en country_id no existe.'];
    const STUDENT_EXCEL_011 = ['code' => 'STUDENT_EXCEL_011', 'message' => 'El valor %s en state_id no existe.'];
    const STUDENT_EXCEL_012 = ['code' => 'STUDENT_EXCEL_012', 'message' => 'El valor %s en city_id no existe.'];
    const STUDENT_EXCEL_013 = ['code' => 'STUDENT_EXCEL_013', 'message' => 'El valor %s en real_entry_date es inválido.'];
    const STUDENT_EXCEL_014 = ['code' => 'STUDENT_EXCEL_014', 'message' => 'El valor %s en nationalized es inválido (debe ser 0 o 1).'];

    /**
     * Obtiene el mensaje de error asociado a un código de error, con soporte para parámetros dinámicos.
     *
     * @param string $code Código de error (por ejemplo, 'FILE_CT_ERROR_008')
     * @param mixed ...$args Argumentos para formatear el mensaje (por ejemplo, valores para %d o %s)
     * @return string Mensaje de error formateado
     * @throws \InvalidArgumentException Si el código no existe
     */
    public static function getMessage(string $code, ...$args): string
    {
        if (defined("self::$code")) {
            $error = constant("self::$code");
            $message = $error['message'] ?? 'Mensaje de error no definido.';
            return vsprintf($message, $args);
        }
        throw new \InvalidArgumentException("Código de error no encontrado: $code");
    }


    /**
     * Obtiene todos los códigos de error definidos en la clase.
     *
     * @return array Lista de códigos de error con sus mensajes
     */
    public static function getAllErrorCodes(): array
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        return array_filter($constants, function ($value) {
            return is_array($value) && isset($value['code']);
        });
    }
}
