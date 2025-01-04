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

    public function stickyNote()
    {
        return $this->hasOne(StickyNote::class, 'project_id', 'id');
    }

    public function miniTextEditor()
    {
        return $this->hasOne(MiniTextEditor::class, 'project_id', 'id');
    }

    public function textCaption()
    {
        return $this->hasOne(TextCaption::class, 'project_id', 'id');
    }

    public function drawing()
    {
        return $this->hasOne(Drawing::class, 'project_id', 'id');
    }
}
