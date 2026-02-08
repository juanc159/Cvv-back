<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityFormResource extends JsonResource
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

            // Contexto
            'company_id' => $this->company_id,
            'teacher_id' => $this->teacher_id,

            // Selects dependientes
            'grade_id' => $this->grade_id,
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,

            // Campos principales
            'title' => $this->title,
            'description' => $this->description,

            // Front usa datetime-local => se suele mandar como "YYYY-MM-DDTHH:mm"
            // Si prefieres devolver ISO completo, dime y lo cambio.
            'deadline_at' => $this->deadline_at?->format('Y-m-d\TH:i'),

            // Status
            'status' => $this->status, 

            // Opcional (útil para UI)
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
