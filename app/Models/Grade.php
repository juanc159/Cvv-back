<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use Cacheable, HasFactory, HasUuids,Searchable;

    protected $casts = [];

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
        return $this->belongsToMany(Subject::class, 'grade_subjects', 'grade_id', 'subject_id');
    }

    // En el modelo Grade
    public function sections()
    {
        return $this->hasManyThrough(Section::class, TeacherComplementary::class, 'grade_id', 'id', 'id', 'section_id');
    }
}
