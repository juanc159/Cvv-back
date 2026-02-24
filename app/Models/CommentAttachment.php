<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommentAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
}
