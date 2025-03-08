<?php

namespace App\Http\Resources\PendingRegistration;

use App\Models\PendingRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource; 

class PendingRegistrationPaginateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'term_id' => $this->term_id,
            'term_name' => $this->term->name, // Nombre del periodo
            'section_name' => $this->section_name,
            'students_count' => $this->students->count(), // Cantidad de alumnos
        ];
    }
 
}
