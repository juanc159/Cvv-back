<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherPlanning extends Model
{
    use HasFactory;


    public function subject()
    {
        return $this->hasOne(Subject::class, 'id', 'subject_id');
    }

    public function grade()
    {
        return $this->hasOne(Grade::class, 'id', 'grade_id');
    }

    public function section()
    {
        return $this->hasOne(Section::class, 'id', 'section_id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'id', 'teacher_id');
    }

}
