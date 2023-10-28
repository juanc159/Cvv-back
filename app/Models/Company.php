<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public function details()
    {
        return $this->hasMany(CompanyDetail::class, "company_id", "id");
    }

    public function banners()
    {
        return $this->hasMany(Banner::class, "company_id", "id");
    }
}
