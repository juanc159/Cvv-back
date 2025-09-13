<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingRegistration extends Model
{
    use HasUuids, SoftDeletes, Cacheable;


    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }


    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function type_education(): BelongsTo
    {
        return $this->belongsTo(TypeEducation::class);
    }
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }


    public function students(): HasMany
    {
        return $this->hasMany(PendingRegistrationStudent::class, 'pending_registration_id');
    }

    public function uniqueSubjects()
    {
        $subjects = $this->students()
            ->with(['subjects.subject' => function ($query) {}])
            ->get()
            ->pluck('subjects')
            ->flatten()
            ->unique('subject_id')
            ->map(function ($subject) {
                return $subject->subject; // Obtiene el modelo Subject relacionado
            });

        // Para cada materia, buscar los archivos asociados
        return $subjects->map(function ($subject) {
            // Buscar archivos en PendingRegistrationFile
            $files = PendingRegistrationFile::where('pending_registration_id', $this->id)
                ->where('subject_id', $subject->id)
                ->get();

            // Agregar los archivos (o array vacío) a la materia
            return [
                'subject' => $subject,
                'files' => $files->isEmpty() ? [] : $files->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'name' => $f->name,
                        'file' => $f->path,
                    ];
                })
            ];
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(PendingRegistrationFile::class, 'pending_registration_id');
    }
}
