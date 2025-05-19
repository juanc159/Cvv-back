<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentListResource extends JsonResource
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
            'photo' => $this->photo,
            'grade_name' => $this->grade?->name,
            'section_name' => $this->section?->name,
            'type_document_name' => $this->type_document?->name,
            'identity_document' => $this->identity_document,
            'full_name' => $this->full_name,
            'type_education_name' => $this->type_education?->name,
        ];
    }
}
