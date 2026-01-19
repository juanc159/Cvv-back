<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\User;

class StudentObserver
{
    /**
     * Handle the Student "updated" event.
     */
    public function updated(Student $student): void
    {
        // Solo actuamos si el estudiante tiene un usuario vinculado
        if ($student->user_id) {
            $user = User::find($student->user_id);

            if ($user) {
                // 1. Sincronizar Nombre
                if ($student->isDirty('full_name')) {
                    // Separamos el nombre nuevamente
                    $parts = explode(' ', $student->full_name);
                    $user->name = $parts[0];
                    $user->surname = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
                }

                // 2. Sincronizar Cédula (CRÍTICO para el login)
                if ($student->isDirty('identity_document')) {
                    $user->identity_document = $student->identity_document;
                }

                // 3. Sincronizar Password (si se cambia desde el perfil)
                if ($student->isDirty('password')) {
                    $user->password = $student->password;
                }
                
                // 4. Sincronizar Estado
                if ($student->isDirty('is_active')) {
                    $user->is_active = $student->is_active;
                }

                // Guardamos los cambios en la tabla maestra silenciosamente
                $user->saveQuietly(); 
            }
        }
    }

    /**
     * Handle the Student "deleted" event.
     */
    public function deleted(Student $student): void
    {
        // Opcional: Si borran al estudiante, ¿borramos al usuario o solo lo desactivamos?
        // Por seguridad, mejor solo desactivarlo o borrarlo si es soft delete.
        if ($student->user_id) {
            $user = User::find($student->user_id);
            if ($user) {
                $user->is_active = false; // Lo desactivamos para que no entre más
                $user->save();
                // $user->delete(); // Descomentar si quieres borrarlo físicamente
            }
        }
    }
}