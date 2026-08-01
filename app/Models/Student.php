<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use Cacheable, HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    protected $casts = [
        'password' => 'hashed',
        'first_time' => 'boolean',
        'is_active' => 'boolean',
        'nationalized' => 'boolean',
        'password' => 'hashed',
    ];

    protected $customCachePrefixes = [
        'string:{table}_statisticsData*',
    ];

    /**
     * Reemplaza un archivo del alumno (foto o boletín) y borra el anterior.
     *
     * store() genera siempre un nombre aleatorio nuevo, así que antes de esto cada carga
     * dejaba el archivo viejo huérfano en el disco: solo se pisaba la ruta en la BD y
     * nadie borraba nada.
     *
     * @param  string  $field  Columna donde se guarda la ruta (photo o boletin)
     * @return string Ruta del archivo nuevo
     */
    public function replaceFile(string $field, UploadedFile $file): string
    {
        $previousPath = $this->getAttribute($field);

        $newPath = $file->store(
            'company_' . $this->company_id . '/student/student_' . $this->id,
            Constants::DISK_FILES
        );

        $this->setAttribute($field, $newPath);
        $this->save();

        // Se borra recién después de guardar: si algo falla antes, el archivo anterior
        // sigue siendo el válido y no perdemos nada.
        if (! empty($previousPath) && $previousPath !== $newPath) {
            try {
                Storage::disk(Constants::DISK_FILES)->delete($previousPath);
            } catch (\Throwable $th) {
                // Que no se pueda borrar el viejo no debe tumbar una carga que ya funcionó.
                Log::warning('No se pudo borrar el archivo anterior del alumno', [
                    'student_id' => $this->id,
                    'field' => $field,
                    'path' => $previousPath,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        return $newPath;
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'student_id', 'id');
    }

    public function type_education()
    {
        return $this->hasOne(TypeEducation::class, 'id', 'type_education_id');
    }

    /**
     * Alias en camelCase de type_education().
     *
     * Student es el único modelo que declara esta relación en snake_case: Teacher, Subject
     * y Grade usan typeEducation(). Al pedir $student->typeEducation, Laravel no encontraba
     * el método y devolvía null en silencio, sin lanzar ningún error. Eso ya provocó que
     * el login del estudiante enviara type_education_name vacío durante quién sabe cuánto.
     *
     * Con este alias las dos formas funcionan y el problema no puede repetirse.
     */
    public function typeEducation()
    {
        return $this->type_education();
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
        return $this->belongsTo(Company::class);
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

    public function pendingRegistrationStudents()
    {
        return $this->hasMany(PendingRegistrationStudent::class);
    }

    public function pendingRegistrationAttempts()
    {
        return $this->hasMany(PendingRegistrationAttempt::class);
    }


    public function type_document()
    {
        return $this->belongsTo(TypeDocument::class);
    }
}
