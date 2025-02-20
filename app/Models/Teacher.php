<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
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
        return $this->name.' '.$this->last_name;
    }

    // Accesor para obtener la URL de la foto si es válida
    public function getPhotoUrlAttribute()
    {
        $photoUrl = $this->photo;

        if (! empty($photoUrl)) {
            return $photoUrl;
        }

        // Si la URL está vacía o el archivo no es accesible, retornar null
        return null;
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
