<?php

namespace App\Helpers;

use App\Helpers\ErrorCollector;
use App\Helpers\ErrorCodes;
use App\Models\TypeEducation;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TypeDocument;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ExcelDataStudentValidator
{
    // Mapeo: 'Columna Excel' => ['db_field' => 'campo BD', 'search_field' => 'campo para buscar en modelo', 'model_key' => 'key para modelo']
    public static array $columnMapping = [
        'TIPO_EDUCACION' => ['db_field' => 'type_education_id', 'search_field' => 'name', 'model_key' => 'type_education'],
        'GRADO' => ['db_field' => 'grade_id', 'search_field' => 'name', 'model_key' => 'grade'],
        'SECTION_ID' => ['db_field' => 'section_id', 'search_field' => 'name', 'model_key' => 'section'],
        'TYPE_DOCUMENT_ID' => ['db_field' => 'type_document_id', 'search_field' => 'name', 'model_key' => 'type_document'],
        'IDENTITY_DOCUMENT' => ['db_field' => 'identity_document', 'search_field' => null, 'model_key' => null], // No DB, solo required
        'FULL_NAME' => ['db_field' => 'full_name', 'search_field' => null, 'model_key' => null], // Solo required
        'GENDER' => ['db_field' => 'gender', 'search_field' => null, 'model_key' => null], // Rename uppercase to lowercase
        'BIRTHDAY' => ['db_field' => 'birthday', 'search_field' => null, 'model_key' => null], // Rename
        'COUNTRY_ID' => ['db_field' => 'country_id', 'search_field' => 'name', 'model_key' => 'country'],
        'STATE_ID' => ['db_field' => 'state_id', 'search_field' => 'name', 'model_key' => 'state'],
        'CITY_ID' => ['db_field' => 'city_id', 'search_field' => 'name', 'model_key' => 'city'],
        'REAL_ENTRY_DATE' => ['db_field' => 'real_entry_date', 'search_field' => null, 'model_key' => null], // Rename
        // 'NATIONALIZED' => ['db_field' => 'nationalized', 'search_field' => null, 'model_key' => null], // Rename uppercase to lowercase
    ];

    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Valida un solo registro (fila) del Excel.
     * Retorna true si hay al menos un error en esta fila.
     * Cachea IDs para mapeo en upsert.
     */
    public static function validateRow(string $batchId, array $row, int $rowIndex, ?string $companyId = null): bool
    {
        $hasRowErrors = false;
        $userRow = $rowIndex + 1;

        // Regla 1: Campos obligatorios (genérico por mapeo)
        foreach (self::$columnMapping as $excelCol => $map) {
            $val = $row[$excelCol] ?? null;
            $isEmpty = empty(trim((string) $val));

            if ($isEmpty) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    $excelCol,
                    ErrorCodes::getMessage('STUDENT_EXCEL_003', ucfirst(str_replace('_', ' ', $excelCol))),
                    ErrorCodes::STUDENT_EXCEL_003['code'],
                    $val,
                    'Campo requerido vacío'
                );
                $hasRowErrors = true;
            }
        }

        // Regla genérica: Validación de existencia y cache de ID (solo para columnas con model_key)
        foreach (self::$columnMapping as $excelCol => $map) {
            if (isset($map['model_key']) && !empty($row[$excelCol])) {
                $exists = self::checkExistsCached($map['model_key'], $row[$excelCol], $batchId, $companyId, $map['search_field']);
                if (!$exists) {
                    ErrorCollector::addError(
                        $batchId,
                        $userRow,
                        $excelCol,
                        ErrorCodes::getMessage('STUDENT_EXCEL_004', $row[$excelCol]), // Código genérico; ajusta por model si quieres específicos
                        ErrorCodes::STUDENT_EXCEL_004['code'],
                        $row[$excelCol],
                        "Valor no existe en tabla {$map['model_key']}"
                    );
                    $hasRowErrors = true;
                } else {
                    // Cachea el ID para mapeo (solo si search_field != 'id', para evitar queries extras)
                    if ($map['search_field'] !== 'id') {
                        self::getCachedId($map['model_key'], $row[$excelCol], $batchId, $companyId, $map['search_field']);
                    }
                }
            }
        }

        // Reglas específicas (no mapeables: gender, birthday, real_entry_date, nationalized) — usa uppercase del Excel
        // Regla 7: gender solo "F" o "M"
        $gender = strtoupper(trim($row['GENDER'] ?? ''));
        if (!empty($row['GENDER']) && !in_array($gender, ['F', 'M'])) {
            ErrorCollector::addError(
                $batchId,
                $userRow,
                'GENDER',
                ErrorCodes::getMessage('STUDENT_EXCEL_008', $row['GENDER']),
                ErrorCodes::STUDENT_EXCEL_008['code'],
                $row['GENDER'],
                'Debe ser "F" o "M"'
            );
            $hasRowErrors = true;
        }

        // Regla 8: birthday válida y < hoy
        if (!empty($row['BIRTHDAY'])) {
            try {
                $birthday = Carbon::parse($row['BIRTHDAY']);
                if (!$birthday->isValid() || $birthday->gte(Carbon::now())) {
                    ErrorCollector::addError(
                        $batchId,
                        $userRow,
                        'BIRTHDAY',
                        ErrorCodes::getMessage('STUDENT_EXCEL_009', $row['BIRTHDAY']),
                        ErrorCodes::STUDENT_EXCEL_009['code'],
                        $row['BIRTHDAY'],
                        'Fecha inválida o no anterior a hoy'
                    );
                    $hasRowErrors = true;
                }
            } catch (\Exception $e) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'BIRTHDAY',
                    ErrorCodes::getMessage('STUDENT_EXCEL_009', $row['BIRTHDAY']),
                    ErrorCodes::STUDENT_EXCEL_009['code'],
                    $row['BIRTHDAY'],
                    'Formato de fecha inválido'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 12: real_entry_date válida y <= hoy
        if (!empty($row['REAL_ENTRY_DATE'])) {
            try {
                $entryDate = Carbon::parse($row['REAL_ENTRY_DATE']);
                if (!$entryDate->isValid() || $entryDate->gt(Carbon::now())) {
                    ErrorCollector::addError(
                        $batchId,
                        $userRow,
                        'REAL_ENTRY_DATE',
                        ErrorCodes::getMessage('STUDENT_EXCEL_013', $row['REAL_ENTRY_DATE']),
                        ErrorCodes::STUDENT_EXCEL_013['code'],
                        $row['REAL_ENTRY_DATE'],
                        'Fecha inválida o posterior a hoy'
                    );
                    $hasRowErrors = true;
                }
            } catch (\Exception $e) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'REAL_ENTRY_DATE',
                    ErrorCodes::getMessage('STUDENT_EXCEL_013', $row['REAL_ENTRY_DATE']),
                    ErrorCodes::STUDENT_EXCEL_013['code'],
                    $row['REAL_ENTRY_DATE'],
                    'Formato de fecha inválido'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 13: nationalized opcional, acepta "SÍ", "NO" o vacío
        $nationalizedVal = $row['NATIONALIZED'] ?? null;
        if (isset($nationalizedVal) && $nationalizedVal !== '') {
            if (!in_array($nationalizedVal, ['SÍ', 'NO'])) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'NATIONALIZED',
                    ErrorCodes::getMessage('STUDENT_EXCEL_014', $nationalizedVal),
                    ErrorCodes::STUDENT_EXCEL_014['code'],
                    $nationalizedVal,
                    'Debe ser SÍ o NO'
                );
                $hasRowErrors = true;
            }
        }

        return $hasRowErrors;
    }

    /**
     * Chequea si un valor existe en el modelo (por campo especificado), cacheado en Redis.
     * Retorna true si existe, false si no.
     */
    private static function checkExistsCached(string $modelKey, mixed $value, string $batchId, ?string $companyId = null, string $searchField = 'id'): bool
    {
        $cacheKey = "batch:{$batchId}:cache:exists:{$modelKey}:{$searchField}:" . ($companyId ? "{$companyId}:" : '') . md5($value); // md5 para keys largas (nombres)

        $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS);

        $cached = $redis->get($cacheKey);
        if ($cached !== null) {
            return (bool) (int) $cached;
        }

        $exists = false;
        $modelClass = self::getModelClassByKey($modelKey);
        if ($modelClass) {
            $query = $modelClass::where($searchField, $value);
            if ($companyId && in_array($modelKey, ['grade', 'section'])) {
                $query->where('company_id', $companyId);
            }
            $exists = $query->exists();
        }

        $redis->setex($cacheKey, self::CACHE_TTL, $exists ? 1 : 0);

        Log::info("DB query and cached: {$modelKey}:{$searchField}:{$value} = " . ($exists ? 'exists' : 'not exists'), ['batchId' => $batchId]);

        return $exists;
    }

    /**
     * Obtiene el ID de un modelo basado en un valor (por campo especificado), cacheado en Redis.
     * Retorna el ID o null si no existe.
     */
    public static function getCachedId(string $modelKey, mixed $value, string $batchId, ?string $companyId = null, string $searchField = 'id')
    {
        $cacheKey = "batch:{$batchId}:cache:id:{$modelKey}:{$searchField}:" . ($companyId ? "{$companyId}:" : '') . md5($value); // md5 para keys largas

        $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS);

        $cachedId = $redis->get($cacheKey);
        if ($cachedId !== null) {
            return  $cachedId ?: null;
        }

        $modelClass = self::getModelClassByKey($modelKey);
        $id = null;
        if ($modelClass) {
            $query = $modelClass::where($searchField, $value);
            if ($companyId && in_array($modelKey, ['grade'])) {
                $query->where('company_id', $companyId);
            }
            $record = $query->first(['id']);
            $id = $record?->id;
        }

        $redis->setex($cacheKey, self::CACHE_TTL, $id ?? 0);

        Log::info("DB query and cached ID: {$modelKey}:{$searchField}:{$value} → ID {$id}", ['batchId' => $batchId]);

        return $id;
    }

    /**
     * Mapea key del modelo a su clase Eloquent.
     */
    public static function getModelClassByKey(string $modelKey): ?string
    {
        return match ($modelKey) {
            'type_education' => TypeEducation::class,
            'grade' => Grade::class,
            'section' => Section::class,
            'type_document' => TypeDocument::class,
            'country' => Country::class,
            'state' => State::class,
            'city' => City::class,
            default => null,
        };
    }
}
