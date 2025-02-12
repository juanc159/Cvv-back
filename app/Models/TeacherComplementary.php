<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherComplementary extends Model
{
    use HasFactory;

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

      // Accesor para obtener las materias asociadas
      public function getSubjectsAttribute()
      {
          // Obtenemos los IDs de las materias desde el campo `subject_ids` que está separado por comas
          $subjectIds = explode(',', $this->subject_ids);
  
          // Buscamos los objetos `Subject` correspondientes a esos IDs
          return Subject::whereIn('id', $subjectIds)->get();
      }
}
