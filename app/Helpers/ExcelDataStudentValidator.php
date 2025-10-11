<?php

namespace App\Helpers;

use App\Helpers\ErrorCollector;
use App\Helpers\ErrorCodes;
use App\Models\TypeEducation; // Ajusta namespaces si difieren
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
    private static array $requiredColumns = [
        'type_education_id',
        'grade_id',
        'section_id',
        'type_document_id',
        'identity_document',
        'full_name',
        'gender',
        'birthday',
        'country_id',
        'state_id',
        'city_id',
        'real_entry_date',
        // Removido: 'nationalized' — ahora opcional
    ];

    private const CACHE_TTL = 3600; // 1 hora; ajusta si necesitas más corto (ej: 600 para 10 min por batch)

    /**
     * Valida un solo registro (fila) del Excel.
     * Retorna true si hay al menos un error en esta fila.
     */
    public static function validateRow(string $batchId, array $row, int $rowIndex, ?string $companyId = null): bool
    {
        $hasRowErrors = false;
        $userRow = $rowIndex + 1; // Fila 1-based para el usuario

        // Regla 1: Campos obligatorios no vacíos (nationalized ya no está aquí)
        foreach (self::$requiredColumns as $col) {
            $val = $row[$col] ?? null;
            $isEmpty = empty(trim((string) $val));

            if ($isEmpty) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    $col,
                    ErrorCodes::getMessage('STUDENT_EXCEL_003', ucfirst(str_replace('_', ' ', $col))),
                    ErrorCodes::STUDENT_EXCEL_003['code'],
                    $val,
                    'Campo requerido vacío'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 3: type_education_id existe (cacheado)
        if (!empty($row['type_education_id'])) {
            $exists = self::checkExistsCached('type_education', $row['type_education_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'type_education_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_004', $row['type_education_id']),
                    ErrorCodes::STUDENT_EXCEL_004['code'],
                    $row['type_education_id'],
                    'ID no existe en tabla type_education'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 4: grade_id existe (cacheado)
        if (!empty($row['grade_id'])) {
            $exists = self::checkExistsCached('grade', $row['grade_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'grade_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_005', $row['grade_id']),
                    ErrorCodes::STUDENT_EXCEL_005['code'],
                    $row['grade_id'],
                    'ID no existe en tabla grades'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 5: section_id existe (cacheado)
        if (!empty($row['section_id'])) {
            $exists = self::checkExistsCached('section', $row['section_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'section_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_006', $row['section_id']),
                    ErrorCodes::STUDENT_EXCEL_006['code'],
                    $row['section_id'],
                    'ID no existe en tabla sections'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 6: type_document_id existe (cacheado)
        if (!empty($row['type_document_id'])) {
            $exists = self::checkExistsCached('type_document', $row['type_document_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'type_document_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_007', $row['type_document_id']),
                    ErrorCodes::STUDENT_EXCEL_007['code'],
                    $row['type_document_id'],
                    'ID no existe en tabla type_documents'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 7: gender solo "F" o "M"
        $gender = strtoupper(trim($row['gender'] ?? ''));
        if (!empty($row['gender']) && !in_array($gender, ['F', 'M'])) {
            ErrorCollector::addError(
                $batchId,
                $userRow,
                'gender',
                ErrorCodes::getMessage('STUDENT_EXCEL_008', $row['gender']),
                ErrorCodes::STUDENT_EXCEL_008['code'],
                $row['gender'],
                'Debe ser "F" o "M"'
            );
            $hasRowErrors = true;
        }

        // Regla 8: birthday válida y < hoy
        if (!empty($row['birthday'])) {
            try {
                $birthday = Carbon::parse($row['birthday']);
                if (!$birthday->isValid() || $birthday->gte(Carbon::now())) { // >= hoy
                    ErrorCollector::addError(
                        $batchId,
                        $userRow,
                        'birthday',
                        ErrorCodes::getMessage('STUDENT_EXCEL_009', $row['birthday']),
                        ErrorCodes::STUDENT_EXCEL_009['code'],
                        $row['birthday'],
                        'Fecha inválida o no anterior a hoy'
                    );
                    $hasRowErrors = true;
                }
            } catch (\Exception $e) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'birthday',
                    ErrorCodes::getMessage('STUDENT_EXCEL_009', $row['birthday']),
                    ErrorCodes::STUDENT_EXCEL_009['code'],
                    $row['birthday'],
                    'Formato de fecha inválido'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 9: country_id existe (cacheado)
        if (!empty($row['country_id'])) {
            $exists = self::checkExistsCached('country', $row['country_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'country_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_010', $row['country_id']),
                    ErrorCodes::STUDENT_EXCEL_010['code'],
                    $row['country_id'],
                    'ID no existe en tabla countries'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 10: state_id existe (cacheado)
        if (!empty($row['state_id'])) {
            $exists = self::checkExistsCached('state', $row['state_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'state_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_011', $row['state_id']),
                    ErrorCodes::STUDENT_EXCEL_011['code'],
                    $row['state_id'],
                    'ID no existe en tabla states'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 11: city_id existe (cacheado)
        if (!empty($row['city_id'])) {
            $exists = self::checkExistsCached('city', $row['city_id'], $batchId, $companyId);
            if (!$exists) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'city_id',
                    ErrorCodes::getMessage('STUDENT_EXCEL_012', $row['city_id']),
                    ErrorCodes::STUDENT_EXCEL_012['code'],
                    $row['city_id'],
                    'ID no existe en tabla cities'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 12: real_entry_date válida y <= hoy
        if (!empty($row['real_entry_date'])) {
            try {
                $entryDate = Carbon::parse($row['real_entry_date']);
                if (!$entryDate->isValid() || $entryDate->gt(Carbon::now())) { // > hoy
                    ErrorCollector::addError(
                        $batchId,
                        $userRow,
                        'real_entry_date',
                        ErrorCodes::getMessage('STUDENT_EXCEL_013', $row['real_entry_date']),
                        ErrorCodes::STUDENT_EXCEL_013['code'],
                        $row['real_entry_date'],
                        'Fecha inválida o posterior a hoy'
                    );
                    $hasRowErrors = true;
                }
            } catch (\Exception $e) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'real_entry_date',
                    ErrorCodes::getMessage('STUDENT_EXCEL_013', $row['real_entry_date']),
                    ErrorCodes::STUDENT_EXCEL_013['code'],
                    $row['real_entry_date'],
                    'Formato de fecha inválido'
                );
                $hasRowErrors = true;
            }
        }

        // Regla 13: nationalized opcional, acepta 0, 1 o vacío (sin error si vacío)
        $nationalizedVal = $row['nationalized'] ?? null;
        if (isset($nationalizedVal) && $nationalizedVal !== '') { // Solo valida si presente y no vacío
            $nationalized = (int) $nationalizedVal;
            if (!in_array($nationalized, [0, 1])) {
                ErrorCollector::addError(
                    $batchId,
                    $userRow,
                    'nationalized',
                    ErrorCodes::getMessage('STUDENT_EXCEL_014', $nationalizedVal),
                    ErrorCodes::STUDENT_EXCEL_014['code'],
                    $nationalizedVal,
                    'Debe ser 0 o 1'
                );
                $hasRowErrors = true;
            }
        }
        // Si vacío o null: pasa sin error (se seteará a 0 en upsert)

        return $hasRowErrors;
    }

    /**
     * Chequea si un ID existe en el modelo, cacheado en Redis.
     * Si no está en cache, consulta DB y guarda.
     * Retorna true si existe, false si no.
     */
    private static function checkExistsCached(string $modelKey, mixed $id, string $batchId, ?string $companyId = null): bool
    {
        $cacheKey = "batch:{$batchId}:cache:exists:{$modelKey}:" . ($companyId ? "{$companyId}:" : '') . "{$id}";

        $redis = Redis::connection(Constants::REDIS_PORT_TO_IMPORTS); // Asumiendo la misma conexión del Job; ajusta si difiere

        // Chequear cache
        $cached = $redis->get($cacheKey);
        if ($cached !== null) {
            return (bool) (int) $cached; // 1=true, 0=false
        }

        // No cacheado: consultar DB
        $exists = false;
        $modelClass = self::getModelClassByKey($modelKey);
        if ($modelClass) {
            $query = $modelClass::where('id', $id);
            // Opcional: filtrar por company_id si aplica (ej: para models multi-tenant)
            if ($companyId && in_array($modelKey, ['grade', 'section'])) { // Ejemplo: solo para algunos models
                $query->where('company_id', $companyId);
            }
            $exists = $query->exists();
        }

        // Guardar en cache
        $redis->setex($cacheKey, self::CACHE_TTL, $exists ? 1 : 0);

        Log::info("DB query and cached: {$modelKey}:{$id} = " . ($exists ? 'exists' : 'not exists'), ['batchId' => $batchId]); // Opcional para debug

        return $exists;
    }

    /**
     * Mapea key del modelo a su clase Eloquent.
     */
    private static function getModelClassByKey(string $modelKey): ?string
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
