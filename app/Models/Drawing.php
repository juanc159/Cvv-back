<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Drawing extends Model
{
    use HasUuids;

    protected $guarded = [];
}
