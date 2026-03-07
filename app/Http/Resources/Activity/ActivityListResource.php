<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'teacher_id' => $this->teacher_id,

            'title' => $this->title,
            'description' => $this->description,

            'deadline_at' => optional($this->deadline_at)->toISOString(),

            'status' => $this->status,
            'status_color' => $this->status?->color(),
            'status_description' => $this->status?->description(),

            'grade' => $this->grade?->name,
            'section' => $this->section?->name,
            'subject' => $this->subject?->name,



            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
