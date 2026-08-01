<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\Activity\ActivitySubmissionStatusEnum; // <--- IMPORTANTE

class ActivitySubmission extends Model
{
    use Cacheable, HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    // 1. CASTS: La magia para que los datos no sean simples strings
    protected $casts = [
        'links' => 'array', // Convierte el JSON de la BD a Array PHP automáticamente
        'status' => ActivitySubmissionStatusEnum::class, // Convierte el string al Enum
        'attempt_number' => 'integer',
    ];

    // 2. RELACIONES: Para saber de quién es la entrega y de qué tarea
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}