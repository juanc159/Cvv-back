<?php

namespace App\Helpers;

/**
 * Normaliza cédulas y nombres para poder compararlos entre el archivo del colegio y
 * la base de datos.
 *
 * El método anterior comparaba con LIKE "%cedula%", que empareja de más: buscar
 * "36430000" también encuentra "1364300005". Aquí la comparación es exacta sobre un
 * valor limpio, que es lo único seguro cuando se van a mover alumnos de grado.
 *
 * En la base todas las cédulas están ya en dígitos puros (sin V-, sin puntos), así que
 * la limpieza se aplica sobre todo a lo que llega del Excel.
 */
class PromotionNormalizer
{
    /**
     * Deja la cédula en dígitos puros.
     *
     * Quita el prefijo de nacionalidad (V, E, J, G, P), puntos, guiones, espacios y
     * cualquier otro adorno. "V-36.479.833" y "36479833" terminan siendo lo mismo.
     */
    public static function document(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Excel a veces entrega los números como "36479833.0" o en notación científica.
        if (is_numeric($value) && str_contains($value, '.')) {
            $value = rtrim(rtrim(number_format((float) $value, 0, '.', ''), '0'), '.');
        }

        $value = mb_strtoupper($value, 'UTF-8');

        // Prefijo de nacionalidad al inicio, con o sin guion: V-, E, J., G ...
        $value = preg_replace('/^\s*[VEJGP]\s*[-.\s]?\s*/u', '', $value);

        // Solo dígitos.
        return preg_replace('/\D/', '', $value) ?? '';
    }

    /**
     * Variante sin ceros a la izquierda, para un segundo intento de coincidencia.
     *
     * Algunos listados traen la cédula rellenada con ceros ("0036479833") y en la base
     * está sin ellos. Se usa solo como respaldo, nunca como comparación principal.
     */
    public static function documentWithoutLeadingZeros(?string $value): string
    {
        $document = self::document($value);
        $trimmed = ltrim($document, '0');

        return $trimmed === '' ? $document : $trimmed;
    }

    /**
     * Normaliza un nombre completo para compararlo.
     *
     * Pasa a mayúsculas, quita tildes y diéresis, convierte la Ñ en N (el mismo alumno
     * aparece como CAÑIZALEZ en un archivo y CANIZALEZ en otro), elimina la coma que
     * separa apellidos de nombres y colapsa los espacios repetidos.
     */
    public static function name(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_strtoupper(trim((string) $value), 'UTF-8');

        $replacements = [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
        ];

        $value = strtr($value, $replacements);

        // Fuera comas, puntos y cualquier signo: solo letras, números y espacios.
        $value = preg_replace('/[^A-Z0-9 ]/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * Normaliza nombres de lugares (estado, municipio, país) para buscarlos en las
     * tablas de geografía, que tienen los nombres con tildes ("Táchira", "San Cristóbal")
     * mientras los archivos del colegio vienen en mayúsculas sin acentos.
     */
    public static function place(?string $value): string
    {
        return self::name($value);
    }

    /**
     * Deduce el tipo de documento por la longitud, que es la convención que ya sigue
     * el colegio: la cédula escolar tiene 11 dígitos y la de identidad 8 (o 10 en el
     * caso de los alumnos colombianos).
     *
     * Es solo una sugerencia: en la pantalla se puede corregir.
     */
    public static function guessDocumentTypeName(string $normalizedDocument): string
    {
        return strlen($normalizedDocument) >= 11
            ? 'Cédula escolar'
            : 'Cédula de identidad';
    }

    /**
     * Interpreta una fecha venida de Excel.
     *
     * Puede llegar como texto ("17/04/2014", "2014-04-17") o como el número de serie
     * que usa Excel internamente (41746). Devuelve null si no se entiende, para que la
     * fila se marque como incompleta en vez de inventar una fecha.
     */
    public static function date($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Número de serie de Excel: días desde el 30/12/1899.
        if (is_numeric($value) && (float) $value > 0 && (float) $value < 100000) {
            try {
                return \Carbon\Carbon::create(1899, 12, 30)
                    ->addDays((int) $value)
                    ->format('Y-m-d');
            } catch (\Throwable $th) {
                return null;
            }
        }

        $value = trim((string) $value);

        // Formatos con día primero, que es como los escribe el colegio.
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'Y-m-d', 'Y/m/d'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $th) {
                // Se intenta el siguiente formato.
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Normaliza el sexo a "F" o "M", que es lo único que acepta la base.
     */
    public static function gender(?string $value): ?string
    {
        $value = mb_strtoupper(trim((string) $value), 'UTF-8');

        if ($value === '') {
            return null;
        }

        $first = mb_substr($value, 0, 1, 'UTF-8');

        return in_array($first, ['F', 'M'], true) ? $first : null;
    }
}
