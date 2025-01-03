<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function joinees()
    {
        return $this->hasMany(Joinee::class, 'project_id', 'id');
    }
}
