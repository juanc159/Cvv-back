<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBatch extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'summary' => 'array',
        'entry_date' => 'date',
        'applied_at' => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(PromotionBatchRow::class, 'promotion_batch_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeEducation()
    {
        return $this->belongsTo(TypeEducation::class, 'type_education_id');
    }
}
