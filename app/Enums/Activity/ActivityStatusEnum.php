<?php

namespace App\Enums\Activity;

use App\Attributes\Color;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum ActivityStatusEnum: string
{
    use AttributableEnum;

    #[Description('Borrrador')]
    #[Color('#54595F')]
    case ACTIVITY_STATUS_001 = 'ACTIVITY_STATUS_001';

    #[Description('Publicado')]
    #[Color('#4CAF50')]
    case ACTIVITY_STATUS_002 = 'ACTIVITY_STATUS_002';

    #[Description('Cerrada')]
    #[Color('#FF5252')]
    case ACTIVITY_STATUS_003 = 'ACTIVITY_STATUS_003';

    /**
     * Genera el array para selectores del Frontend
     */
    public static function toOptions(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'title' => $case->description(),
        ], self::cases());
    }

    /**
     * Retorna un array asociativo [Descripción => Valor] para usar en filtros.
     * Ejemplo: ['Creación' => 'created', 'Actualización' => 'updated']
     */
    public static function toFilterMap(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->description()] = $case->value;
        }

        return $map;
    }
 
}
