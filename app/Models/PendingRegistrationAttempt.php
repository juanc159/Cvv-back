<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingRegistrationAttempt extends Model
{
    use HasUuids, SoftDeletes, Cacheable;

    protected $casts = [
        'approved' => 'boolean',
        'grade' => 'decimal:2',
        'attempt_date' => 'date',
    ];

    public function pendingRegistration()
    {
        return $this->belongsTo(PendingRegistration::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
