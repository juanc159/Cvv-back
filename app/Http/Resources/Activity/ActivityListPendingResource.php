<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ActivityListPendingResource extends JsonResource
{
    public function toArray($request)
    {
        // Calculamos el estado basado en la fecha límite
        $deadline = $this->deadline_at ? Carbon::parse($this->deadline_at) : null;
        $now = now();
        
        $statusText = 'Vigente';
        $statusColor = 'success'; // Verde por defecto

        if (!$deadline) {
            $statusText = 'Sin fecha';
            $statusColor = 'info';
        } else {
            if ($now->gt($deadline)) {
                // Si hoy es mayor a la fecha límite
                $statusText = 'Vencida';
                $statusColor = 'error'; // Rojo
            } elseif ($now->diffInDays($deadline, false) <= 2 && $now->diffInDays($deadline, false) >= 0) {
                // Si faltan 2 días o menos (y no está vencida)
                $statusText = 'Por vencer';
                $statusColor = 'warning'; // Amarillo/Naranja
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description, // Opcional, si lo necesitas en la lista
            'deadline_at' => $this->deadline_at,
            'status' => $this->status, // El status técnico (001, 002)
            
            // CAMPOS CALCULADOS PARA EL FRONT
            'status_text' => $statusText,
            'status_color' => $statusColor,

            // Relaciones
            'subject' => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name
            ] : null,
            
            'teacher' => $this->teacher ? [
                'id' => $this->teacher->id,
                'first_name' => $this->teacher->first_name,
                'last_name' => $this->teacher->last_name,
                'full_name' => $this->teacher->first_name . ' ' . $this->teacher->last_name,
            ] : null,
            
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}