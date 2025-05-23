<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherFormResource extends JsonResource
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
            'company_id' => $this->company_id,
            'type_education_id' => $this->type_education_id,
            'job_position_id' => $this->job_position_id,
            'complementaries' => $this->complementaries->map(function ($value) {

                $dataSub = $value->subjects;

                return [
                    'id' => $value->id,
                    'grade_id' => $value->grade_id,
                    'grade_name' => $value->grade?->name,
                    'section_id' => $value->section_id,
                    'section_name' => $value->section?->name,
                    'subjects' => $dataSub->map(function ($subject) {
                        return [
                            'value' => $subject->id,
                            'title' => $subject->name, // Ajusta según los campos de Subject
                        ];
                    })->all(),
                    'delete' => 0,
                ];
            }),
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,
        ];
    }
}
