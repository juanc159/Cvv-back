<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use Cacheable, HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function state()
    {
        return $this->hasOne(State::class, 'id', 'state_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }

    public function details()
    {
        return $this->hasMany(CompanyDetail::class, 'company_id', 'id');
    }

    public function banners()
    {
        return $this->hasMany(Banner::class, 'company_id', 'id');
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'company_id', 'id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'company_id', 'id');
    }
}
