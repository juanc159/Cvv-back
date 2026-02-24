<?php

namespace App\QueryBuilder\Sort;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class RelatedTableSort implements Sort
{
    private string $primaryTable;      // Tabla principal (ej: inspections)
    private string $relatedTable;      // Tabla relacionada (ej: vehicles)
    private string $sortField;         // Campo por el que se ordena (ej: license_plate)
    private string $foreignKey;        // Llave foránea en la tabla principal (ej: vehicle_id)
    private string $alias;             // Alias único para la tabla relacionada

    public function __construct(string $primaryTable, string $relatedTable, string $sortField, string $foreignKey)
    {
        $this->primaryTable = $primaryTable;
        $this->relatedTable = $relatedTable;
        $this->sortField = $sortField;
        $this->foreignKey = $foreignKey;
        // Generar un alias único basado en la tabla y el campo de ordenación
        $this->alias = "{$relatedTable}_for_{$sortField}";
    }

    public function __invoke(Builder $query, bool $descending, string $property): Builder
    {
        $direction = $descending ? 'desc' : 'asc';

        // 1. CAMBIO: Usar leftJoin en lugar de join para no perder registros con FK null
        $query->leftJoin("{$this->relatedTable} as {$this->alias}", "{$this->primaryTable}.{$this->foreignKey}", '=', "{$this->alias}.id")
              ->orderBy("{$this->alias}.{$this->sortField}", $direction);

        // 2. SEGURIDAD: Asegurarnos de seleccionar solo los campos de la tabla principal
        // Si no hacemos esto, el 'id' de la tabla relacionada sobrescribirá el 'id' de la tabla principal
        if (!$query->getQuery()->columns) {
            $query->select("{$this->primaryTable}.*");
        } elseif (!in_array("{$this->primaryTable}.*", $query->getQuery()->columns)) {
            $query->addSelect("{$this->primaryTable}.*");
        }

        return $query;
    }
}
