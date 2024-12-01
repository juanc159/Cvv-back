<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_education_name' => $this->typeEducation?->name,
            'job_position_name' => $this->jobPosition?->name,
            'subjects' => $this->subjects ? $this->subjects->pluck('name') : [],
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'is_active' => $this->is_active,
        ];
    }
}
