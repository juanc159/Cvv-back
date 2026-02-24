<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Comment extends Model
{
    use Cacheable, HasFactory, HasUuids, SoftDeletes;
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_internal' => 'boolean',
    ];

    protected $customCachePrefixes = [
        'string:{table}_list*',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function commentable()
    {
        return $this->morphTo(__FUNCTION__, 'commentable_type', 'commentable_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Nueva relación para los adjuntos
    public function attachments()
    {
        return $this->hasMany(CommentAttachment::class);
    }
}
