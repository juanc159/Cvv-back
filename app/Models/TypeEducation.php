<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeEducation extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'cantNotes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'type_education_id', 'id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'type_education_id', 'id');
    }

    public function note_selections()
    {
        return $this->hasMany(TypeEducationNoteSelection::class, 'type_education_id', 'id');
    }
}
