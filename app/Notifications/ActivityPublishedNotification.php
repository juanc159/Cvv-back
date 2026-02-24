<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $activity;

    /**
     * Recibimos la actividad creada/publicada.
     */
    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Definimos los canales. Por ahora 'database' para la campanita del sistema.
     * Más adelante podrías agregar 'mail'.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Estructura de datos que se guardará en la tabla 'notifications'.
     * Esto es lo que consumirá el Frontend para mostrar la alerta.
     */
    public function toArray($notifiable)
    {
        return [
            'activity_id' => $this->activity->id,
            'title' => $this->activity->title,
            'subject' => $this->activity->subject->name ?? 'Materia desconocida', // Asumiendo relación
            'deadline' => $this->activity->deadline_at,
            'message' => "Nueva actividad publicada: {$this->activity->title}",
            'type' => 'activity_assigned', // Para diferenciar tipos de notificaciones
            'action_url' => "/student/activities" // A dónde debe ir el alumno al dar click
        ];
    }
}