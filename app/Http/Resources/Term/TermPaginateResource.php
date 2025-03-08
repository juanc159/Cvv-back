<?php

namespace App\Http\Resources\Term;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TermPaginateResource extends JsonResource
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
            'start_date' => Carbon::parse($this->start_date)->format("d-m-Y"),
            'end_date' => Carbon::parse($this->end_date)->format("d-m-Y"),
            'is_active' => $this->is_active,
        ];
    }
}
