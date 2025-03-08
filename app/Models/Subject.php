<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use Cacheable, HasFactory, HasUuids, Searchable;

    protected $casts = [
    ];

    public function typeEducation()
    {
        return $this->hasOne(TypeEducation::class, 'id', 'type_education_id');
    }
}
