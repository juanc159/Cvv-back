<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $casts = [
        'company_id' => 'integer',
        'type_education_id' => 'integer',
        'job_position_id' => 'integer',
    ];

    public function complementaries()
    {
        return $this->hasMany(TeacherComplementary::class, 'teacher_id', 'id');
    }

    public function typeEducation()
    {
        return $this->hasOne(TypeEducation::class, 'id', 'type_education_id');
    }

    public function jobPosition()
    {
        return $this->hasOne(JobPosition::class, 'id', 'job_position_id');
    }
}
