<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 

class PendingRegistrationFile extends Model
{
    use HasUuids, Cacheable;

    public function pending_registration(): BelongsTo
    {
        return $this->belongsTo(PendingRegistration::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
