<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Term extends Model
{
    use HasUuids, SoftDeletes, Cacheable;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
