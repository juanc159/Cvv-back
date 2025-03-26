<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use Cacheable, HasFactory, HasUuids,Searchable;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $customCachePrefixes = [
        'string:{table}_list*',
        'string:{table}_listPw*',
        'string:{table}_wherePw*',

    ];
}
