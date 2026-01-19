<?php

namespace App\Observers;

use App\Models\Teacher;
use App\Models\User;

class TeacherObserver
{
    /**
     * Handle the Teacher "updated" event.
     */
    public function updated(Teacher $teacher): void
    {
        if ($teacher->user_id) {
            $user = User::find($teacher->user_id);

            if ($user) {
                // 1. Sincronizar Nombres
                if ($teacher->isDirty(['name', 'last_name'])) {
                    $user->name = $teacher->name;
                    $user->surname = $teacher->last_name; // Mapeamos last_name a surname
                }

                // 2. Sincronizar Email (Login del profe)
                if ($teacher->isDirty('email')) {
                    $user->email = $teacher->email;
                }

                // 3. Sincronizar Password
                if ($teacher->isDirty('password')) {
                    $user->password = $teacher->password;
                }
                
                 // 4. Sincronizar Estado
                if ($teacher->isDirty('is_active')) {
                    $user->is_active = $teacher->is_active;
                }

                $user->saveQuietly();
            }
        }
    }

    public function deleted(Teacher $teacher): void
    {
        if ($teacher->user_id) {
            $user = User::find($teacher->user_id);
            if ($user) {
                $user->is_active = false;
                $user->save();
            }
        }
    }
}