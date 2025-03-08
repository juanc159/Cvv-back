<?php

namespace App\Http\Resources\Term;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TermFormResource extends JsonResource
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
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date, 
        ];
    }
}
