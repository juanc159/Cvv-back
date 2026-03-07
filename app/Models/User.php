<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Cacheable, HasApiTokens, HasFactory, HasPermissions, HasRoles, HasUuids, Notifiable, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Método de acceso para combinar nombre y apellido
    public function getFullNameAttribute()
    {
        return $this->name.' '.$this->surname;
    }

    /**
     * Accesor para obtener la foto del perfil (Teacher o Student).
     * Busca primero en el perfil de profesor, luego en el de estudiante.
     *
     * @return string|null
     */
    public function getPhotoAttribute(): ?string
    {
        // Prioriza la foto del profesor, si no, la del estudiante.
        return $this->teacher?->photo ?? $this->student?->photo;
    }

    public function scopeSearchFullName($query, $value)
    {
        if (empty($value)) {
            return $query;
        }

        return $query->whereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$value}%"]);
    }

    public function getAllPermissionsAttribute()
    {
        return $this->getAllPermissions();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

     public function notificaciones()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Relación: Un User tiene un perfil de Estudiante asociado
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'id');
    }

    // Relación: Un User tiene un perfil de Profesor asociado
    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id', 'id');
    }
}
