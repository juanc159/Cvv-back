<?php

namespace App\Enums\Activity;

use App\Attributes\Color;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum ActivitySubmissionStatusEnum: string
{
    use AttributableEnum;

    // 1. EL ALUMNO ESTÁ TRABAJANDO PERO NO HA ENVIADO
    #[Description('Borrador')]
    #[Color('#6c757d')] // Gris
    case ACTIVITY_SUBMISSION_STATUS_001 = 'ACTIVITY_SUBMISSION_STATUS_001';

    // 2. EL ALUMNO YA LO ENVIÓ AL PROFESOR
    #[Description('Entregado')]
   #[Color('#28a745')] // Verde (success)
    case ACTIVITY_SUBMISSION_STATUS_002 = 'ACTIVITY_SUBMISSION_STATUS_002';

    // 3. EL PROFESOR LO VIO Y PIDE CORRECCIONES (FEEDBACK)
    #[Description('Requiere Corrección')]
    #[Color('#e6862c')] // Naranja (warning)
    case ACTIVITY_SUBMISSION_STATUS_003 = 'ACTIVITY_SUBMISSION_STATUS_003';

    // 4. EL PROFESOR DA EL VISTO BUENO (FINALIZADO)
    #[Description('Revisado')]
    #[Color('#17a2b8')] // Azul (info)
    case ACTIVITY_SUBMISSION_STATUS_004 = 'ACTIVITY_SUBMISSION_STATUS_004';

    /**
     * Helper para selectores
     */
    public static function toOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'title' => $case->description(),
            'color' => $case->color(), // Útil para el front
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
