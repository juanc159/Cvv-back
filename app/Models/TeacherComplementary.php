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
        // Convertir la cadena en array y limpiar espacios
        $subjectIds = array_map('trim', explode(',', $this->subject_ids));

        // Verificar si hay IDs válidos
        if (empty($subjectIds) || $subjectIds[0] === '') {
            return collect(); // Devuelve colección vacía si no hay IDs
        }

        // Buscar los sujetos
        return Subject::whereIn('id', $subjectIds)->get();
    }
}
