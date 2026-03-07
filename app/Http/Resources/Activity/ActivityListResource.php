<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ActivityListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description, // Opcional, si lo necesitas en la lista
            'deadline_at' => Carbon::parse($this->deadline_at)->format("d-m-Y H:i"),
            'status' => $this->status,
            'status_description' =>  $this->status?->description(),
            'status_color' =>  $this->status?->color(),
             

            // Relaciones
            'subject' => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name
            ] : null,
        ];
    }
}