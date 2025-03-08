<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

trait AuditMappingTrait
{
    /**
     * Aplica mapeo de columnas y descripciones a una colección de auditorías.
     *
     * @param  array|\Illuminate\Support\Collection  &$audits  Colección de auditorías
     */
    public function applyColumnMappingToAudits(&$audits): void
    {
        foreach ($audits as $audit) {
            try {
                // Resolver el modelo asociado al audit
                $modelClass = $audit->auditable_type;
                $model = app($modelClass);

                // Aplicar mapeo de columnas si existe configuración
                if (method_exists($model, 'getColumnsConfig')) {
                    $columns = $model->getColumnsConfig();
                    $audit->old_values = $this->applyColumnMapping($audit->old_values, $columns);
                    $audit->new_values = $this->applyColumnMapping($audit->new_values, $columns);
                }

                // Aplicar descripción de acción si existe
                if (method_exists($model, 'getActionDescription')) {
                    $audit->action = $model->getActionDescription($audit->event);
                }
            } catch (Exception $e) {
                // Opcional: registrar error en log o lanzar excepción
                continue; // Saltar esta auditoría si falla
            }
        }
    }

    /**
     * Transforma un array de valores aplicando un mapeo de columnas.
     *
     * @param  array|null  $values  Valores a transformar (old_values o new_values)
     * @param  array|null  $columns  Configuración de columnas del modelo
     * @return array|null Valores transformados o null si no hay valores
     */
    protected function applyColumnMapping(?array $values, ?array $columns): ?array
    {
        if (! $values || ! $columns) {
            return $values;
        }

        $transformed = [];
        $tableLookups = [];
        $modelLookups = [];

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $columns)) {
                continue; // Ignorar claves no configuradas
            }

            $config = $columns[$key];
            $newKey = $config['label'] ?? $key;

            // Valor inicial
            $transformed[$newKey] = $value;

            // Aplicar función si existe
            if (isset($config['function']) && is_callable($config['function'])) {
                $transformed[$newKey] = $config['function']($value);
            }

            // Formatear como fecha si es tipo date
            elseif (isset($config['type']) && $config['type'] === 'date' && $value) {
                $format = $config['format'] ?? 'Y-m-d';
                $transformed[$newKey] = Carbon::parse($value)->format($format);
            }

            // Consulta a tabla relacionada
            elseif (isset($config['table']) && isset($config['table_field'])) {
                $tableLookups[$config['table']][$key] = [
                    'value' => $value,
                    'field' => $config['table_field'],
                    'newKey' => $newKey,
                ];
            }

            // Consulta a modelo relacionado
            elseif (isset($config['model']) && isset($config['model_field'])) {
                $modelLookups[$config['model']][$key] = [
                    'value' => $value,
                    'field' => $config['model_field'],
                    'newKey' => $newKey,
                ];
            }
        }

        // Procesar consultas en lote para tablas
        foreach ($tableLookups as $table => $lookups) {
            $ids = array_column($lookups, 'value');
            $results = DB::table($table)->whereIn('id', $ids)->get()->pluck($lookups[$key]['field'], 'id')->toArray();
            foreach ($lookups as $lookup) {
                $transformed[$lookup['newKey']] = $results[$lookup['value']] ?? null;
            }
        }

        // Procesar consultas en lote para modelos
        foreach ($modelLookups as $modelName => $lookups) {
            $modelClass = "App\Models\\".$modelName;
            $ids = array_column($lookups, 'value');
            $results = $modelClass::whereIn('id', $ids)->get()->pluck($lookups[$key]['field'], 'id')->toArray();
            foreach ($lookups as $lookup) {
                $transformed[$lookup['newKey']] = $results[$lookup['value']] ?? null;
            }
        }

        return $transformed;
    }
}
