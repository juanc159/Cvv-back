<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockData extends Model
{
    use HasFactory,HasUuids;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Indica si un interruptor está encendido.
     *
     * Si la fila no existe se devuelve $default en vez de reventar, para que un
     * interruptor sin sembrar no tumbe el login ni la descarga de documentos.
     */
    public static function isActive(string $name, bool $default = false): bool
    {
        $row = static::where('name', $name)->first();

        return $row ? (bool) $row->is_active : $default;
    }
}
