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
            // Ajustamos el formato a 12 horas con AM/PM y aseguramos la zona horaria.
            'deadline_at' => $this->deadline_at ? Carbon::parse($this->deadline_at)->setTimezone(config('app.timezone'))->format("d-m-Y h:i A") : null,
            'status' => $this->status,
            'status_description' =>  $this->status?->description(),
            'status_color' =>  $this->status?->color(),
            'grade' => $this->grade?->name,
            'section' => $this->section?->name,
             

            // Relaciones
            'subject' => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name
            ] : null,
        ];
    }
}