<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Http;

class Student extends Authenticatable
{
    use HasFactory, HasApiTokens,Searchable;

    protected $casts = [
        'password' => 'hashed',
    ];

    public function notes()
    {
        return $this->hasMany(Note::class, "student_id", "id");
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

    // Accesor para obtener la URL de la foto si es válida
    public function getPhotoUrlAttribute()
    {
        $photoUrl = $this->photo;

        if (!empty($photoUrl)) {
            // Verificar si la URL es accesible
            try {
                $response = Http::head($photoUrl);

                if ($response->successful()) {
                    // La URL es accesible, devolverla
                    return $photoUrl;
                }
            } catch (\Exception $e) {
                // Manejar excepciones si es necesario
            }
        }

        // Si la URL está vacía o el archivo no es accesible, retornar null
        return null;
    }

    // Relación con TeacherPlanning
    public function teacherPlannings()
    {
        return $this->hasMany(TeacherPlanning::class, 'grade_id', 'grade_id')
                    ->where('section_id', $this->section_id);
    }
}
