<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Country\CountrySelectResource;
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
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'real_entry_date' => $this->real_entry_date,
            'country_id' => new CountrySelectResource($this->country),
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
        ];
    }
}
