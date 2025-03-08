<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingRegistration extends Model
{
    use HasUuids,SoftDeletes,Cacheable;
 
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
 
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

   
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

   
    public function students(): HasMany
    {
        return $this->hasMany(PendingRegistrationStudent::class, 'pending_registration_id');
    }
   
    
}
