<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Resultado de una corrida de auditoría del módulo de Mantenimiento.
 */
class MaintenanceAudit extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'summary' => 'array',
        'findings' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const TYPE_FILES = 'files';

    public const TYPE_CLEANUP = 'cleanup';

    public const TYPE_DATA = 'data';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** Una corrida terminó (bien o mal) y ya no hay que seguir consultándola. */
    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
