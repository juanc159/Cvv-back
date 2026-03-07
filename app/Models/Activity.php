<?php

namespace App\Models;

use App\Enums\Activity\ActivityStatusEnum;
use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use Cacheable, HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'deadline_at' => 'datetime',
        'status' => ActivityStatusEnum::class,
    ];

    // ✅ Docente (tu tabla real es teachers)
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    // ✅ Grado / Año
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    // ✅ Sección
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // ✅ Materia
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ActivityAssignment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
