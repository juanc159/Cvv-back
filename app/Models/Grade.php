<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory,Searchable;

    protected $casts = [
        'type_education_id' => 'integer',
    ];

    public function teachers()
    {
        return $this->hasMany(TeacherComplementary::class, 'grade_id', 'id');
    }


    public function typeEducation()
    {
        return $this->hasOne(TypeEducation::class, 'id', 'type_education_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'grade_subjects', 'grade_id', "subject_id");
    }
}
