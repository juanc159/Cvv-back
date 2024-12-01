<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentFormResource extends JsonResource
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
            'grade_id' => $this->grade_id,
            'section_id' => $this->section_id,
            'identity_document' => $this->identity_document,
            'full_name' => $this->full_name,
            'pdf' => $this->pdf,
            'photo' => $this->photo,
        ];
    }
}
