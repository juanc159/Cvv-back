<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory,Searchable;

    protected $casts = [
        'type_education_id' => 'integer',
    ];

    public function typeEducation()
    {
        return $this->hasOne(TypeEducation::class, 'id', 'type_education_id');
    }
}
