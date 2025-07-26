<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory, HasUuids, Searchable;

    protected $guarded = [];
    public function subject()
    {
        return $this->hasOne(Subject::class, 'id', 'subject_id');
    }
    public function student()
    {
        return $this->hasOne(student::class, 'id', 'student_id');
    }
}
