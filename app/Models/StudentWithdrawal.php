<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StudentWithdrawal extends Model
{
    use HasUuids;

    protected $guarded = [];

    // Relationship with Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
