<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    public function notes(){
        return $this->hasMany(Note::class,"student_id","id");
    }

    public function typeEducation()
    {
        return $this->hasOne(TypeEducation::class, 'id', 'type_education_id');
    }
    public function grade()
    {
        return $this->hasOne(Grade::class, 'id', 'grade_id');
    }
    public function section()
    {
        return $this->hasOne(Section::class, 'id', 'section_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
