<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBatchRow extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'messages' => 'array',
        'payload' => 'array',
        'needs_review' => 'boolean',
        'is_approved' => 'boolean',
        'is_applied' => 'boolean',
    ];

    // Acciones posibles de una fila. Ver el comentario de la migración para el detalle.
    public const ACTION_PROMOTE = 'promote';
    public const ACTION_STAY = 'stay';
    public const ACTION_REPEAT = 'repeat';
    public const ACTION_REINSTATE = 'reinstate';
    public const ACTION_UPDATE_DOCUMENT = 'update_document';
    public const ACTION_CREATE = 'create';
    public const ACTION_UNASSIGN = 'unassign';
    public const ACTION_ERROR = 'error';

    public const MATCH_EXACT = 'exact';
    public const MATCH_NAME_BIRTHDAY = 'name_birthday';
    public const MATCH_NAME = 'name';
    public const MATCH_NONE = 'none';

    /**
     * Nombre legible de cada acción.
     *
     * Vive aquí y no en el controlador porque lo usan tanto la pantalla como el reporte
     * en Excel, y si se escriben por separado terminan diciendo cosas distintas.
     */
    public static function actionLabels(): array
    {
        return [
            self::ACTION_PROMOTE => 'Cambian de grado',
            self::ACTION_STAY => 'Ya estaban en este grado y sección',
            self::ACTION_REPEAT => 'Repiten grado',
            self::ACTION_REINSTATE => 'Se reintegran al colegio',
            self::ACTION_CREATE => 'Alumnos nuevos',
            self::ACTION_UNASSIGN => 'Salen de esta sección',
            self::ACTION_ERROR => 'No se pueden procesar',
        ];
    }

    /**
     * Orden en que conviene revisar los grupos: primero lo que exige decisión.
     */
    public static function actionOrder(): array
    {
        return [
            self::ACTION_ERROR,
            self::ACTION_CREATE,
            self::ACTION_REINSTATE,
            self::ACTION_REPEAT,
            self::ACTION_PROMOTE,
            self::ACTION_STAY,
            self::ACTION_UNASSIGN,
        ];
    }

    /**
     * Cómo se emparejó al alumno, en palabras.
     */
    public static function matchLabels(): array
    {
        return [
            self::MATCH_EXACT => 'Por cédula',
            self::MATCH_NAME_BIRTHDAY => 'Por nombre y fecha de nacimiento',
            self::MATCH_NAME => 'Solo por nombre',
            self::MATCH_NONE => 'Sin coincidencia',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(PromotionBatch::class, 'promotion_batch_id', 'id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
