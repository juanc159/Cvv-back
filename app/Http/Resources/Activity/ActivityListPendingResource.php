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
        $isOverdue = $deadline ? now()->gt($deadline) : false;

        $statusText = 'Vigente'; // Valor por defecto
        if (!$deadline) {
            $statusText = 'Sin fecha';
        } elseif ($isOverdue) {
            $statusText = 'Vencida';
        }

        // Determinar el estado de la entrega del alumno
        $latestSubmission = $this->whenLoaded('latestSubmission');
        $submissionStatus = $latestSubmission?->status;

        // Lógica de estado de entrega más explícita
        $submissionStatusValue = null;
        $submissionStatusDescription = 'Pendiente';
        $submissionStatusColor = 'warning';

        // Si hay una entrega, usamos su estado.
        if ($submissionStatus) {
            $submissionStatusValue = $submissionStatus->value;
            $submissionStatusDescription = $submissionStatus->description();
            $submissionStatusColor = $submissionStatus->color();
        } 
        // Si NO hay entrega Y la tarea está vencida, cambiamos el estado a "Vencida".
        elseif ($isOverdue) {
            $submissionStatusDescription = 'Vencida';
            $submissionStatusColor = 'error'; // Usamos el color de error (rojo)
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description, // Opcional, si lo necesitas en la lista
            // Formateamos la fecha a la zona horaria de la app y en formato 12h AM/PM
            'deadline_at' => $this->deadline_at ? Carbon::parse($this->deadline_at)->setTimezone(config('app.timezone'))->format("d-m-Y h:i A") : null,
            'deadline_at_iso' => $this->deadline_at ? Carbon::parse($this->deadline_at)->toIso8601String() : null, // Para comparaciones en JS
            'is_overdue' => $isOverdue, // El booleano que necesitamos
            'status' => $this->status, // El status técnico de la actividad (Borrador, Publicado)

            // NUEVO: El estado de la ENTREGA del alumno
            'submission_status' => $submissionStatusValue,
            'submission_status_description' => $submissionStatusDescription,
            'submission_status_color' => $submissionStatusColor,

            // CAMPOS CALCULADOS PARA EL FRONT
            'status_text' => $statusText,

            // Relaciones
            'subject' => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name
            ] : null,
            
            'teacher' => $this->teacher ? [
                'full_name' => $this->teacher->user?->full_name ?? 'Docente Asignado',
            ] : null,
            
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}