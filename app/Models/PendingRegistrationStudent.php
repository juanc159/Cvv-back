<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingRegistrationStudent extends Model
{
    use HasUuids, SoftDeletes, Cacheable;


    public function pendingRegistration(): BelongsTo
    {
        return $this->belongsTo(PendingRegistration::class, 'pending_registration_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(PendingRegistrationSubject::class, 'pending_registration_student_id');
    }


    public function pendingRegistrationSubjects()
    {
        return $this->hasMany(PendingRegistrationSubject::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
