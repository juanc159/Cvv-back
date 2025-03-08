<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class Student extends Model
{
    use Cacheable, HasApiTokens, HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    protected $casts = [
        'password' => 'hashed',
        'first_time' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    protected $customCachePrefixes = [
        'string:{table}_statisticsData*',
    ]; 

    public function notes()
    {
        return $this->hasMany(Note::class, 'student_id', 'id');
    }

    public function type_education()
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

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
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

    // Relación con TeacherPlanning
    public function teacherPlannings()
    {
        return $this->hasMany(TeacherPlanning::class, 'grade_id', 'grade_id')
            ->where('section_id', $this->section_id);
    }

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function state()
    {
        return $this->hasOne(State::class, 'id', 'state_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }

    /**
     * Relación con StudentWithdrawal.
     * Retorna el registro de retiro si existe.
     */
    public function withdrawal()
    {
        return $this->hasOne(StudentWithdrawal::class, 'student_id');
    }

    /**
     * Método para verificar si el estudiante está retirado.
     */
    public function isWithdrawn(): bool
    {
        return $this->withdrawal()->exists();
    }
}
