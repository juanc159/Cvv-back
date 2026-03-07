<?php

namespace App\QueryBuilder\Sort;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class EnumDescriptionSort implements Sort
{
    protected string $enumClass;

    public function __construct(string $enumClass)
    {
        $this->enumClass = $enumClass;
    }

    public function __invoke(Builder $query, bool $descending, string $property)
    {
        // Iniciamos la construcción del CASE
        $sql = "CASE {$property} ";

        // Recorremos el Enum para mapear Valor => Descripción
        foreach ($this->enumClass::cases() as $case) {
            // Escapamos valores por seguridad, aunque viniendo de un Enum es seguro
            $value = $case->value;
            $description = $case->description();

            $sql .= "WHEN '{$value}' THEN '{$description}' ";
        }

        // Si hay algún valor en BD que no esté en el Enum, usamos el valor original
        $sql .= "ELSE {$property} END";

        $direction = $descending ? 'DESC' : 'ASC';

        $query->orderByRaw("{$sql} {$direction}");
    }
}
