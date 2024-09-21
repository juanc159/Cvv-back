<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\HasApiTokens;

class Teacher extends Authenticatable
{
    use HasFactory,SoftDeletes,HasApiTokens;

    protected $casts = [
        'company_id' => 'integer',
        'type_education_id' => 'integer',
        'job_position_id' => 'integer',
        'password' => 'hashed',
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

     // Accesor para obtener el nombre completo
     public function getFullNameAttribute()
     {
         return $this->name . ' ' . $this->last_name;
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

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
