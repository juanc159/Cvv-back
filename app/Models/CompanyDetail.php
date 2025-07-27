<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    use HasFactory, HasUuids;

    public function typeDetail()
    {
        return $this->hasOne(TypeDetail::class, 'id', 'type_detail_id');
    }
}
