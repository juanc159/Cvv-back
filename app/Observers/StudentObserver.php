<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Mantiene la tabla users al día con la ficha del alumno.
 *
 * users es por donde el alumno entra al sistema (el login se autentica contra ella y solo
 * después carga el perfil de students), así que todo dato compartido tiene que viajar de la
 * ficha hacia el usuario. La dirección contraria no existe a propósito: el módulo de Usuarios
 * solo lista administradores, y dos tablas copiándose entre sí se desincronizan el doble.
 *
 * Ojo: esto es un observador de Eloquent. No se dispara con Student::upsert() ni con
 * DB::table()->update(), así que esos caminos dejan users desactualizado en silencio.
 */
class StudentObserver
{
    public function updated(Student $student): void
    {
        if (! $student->user_id) {
            return;
        }

        $user = User::find($student->user_id);

        if (! $user) {
            return;
        }

        if ($student->isDirty('full_name')) {
            $parts = explode(' ', $student->full_name);
            $user->name = $parts[0];
            $user->surname = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
        }

        // Crítico para el login: es con esta cédula con la que el alumno entra.
        if ($student->isDirty('identity_document')) {
            $user->identity_document = $student->identity_document;
        }

        if ($student->isDirty('password')) {
            $user->password = $student->password;
        }

        if ($student->isDirty('is_active')) {
            $user->is_active = $student->is_active;
        }

        // Si al alumno lo cambian de colegio, su acceso tiene que mudarse con él. Faltaba, y
        // ya dejó a un alumno con la ficha en un colegio y el usuario en otro.
        if ($student->isDirty('company_id')) {
            $user->company_id = $student->company_id;
        }

        $user->saveQuietly();
    }

    /**
     * Al borrar un alumno se borra también su usuario.
     *
     * Antes solo se desactivaba, y como users.identity_document es único en todo el sistema,
     * la cédula quedaba ocupada para siempre por un registro invisible desde el módulo de
     * estudiantes: al volver a registrar al alumno saltaba "El número de documento ya está
     * registrado" sin que hubiera ningún alumno usándola.
     *
     * Borrarlo es coherente con el esquema: students.user_id es ON DELETE CASCADE hacia users,
     * o sea el usuario ya es el registro padre. Aquí el alumno ya se borró, así que la cascada
     * no arrastra nada más.
     */
    public function deleted(Student $student): void
    {
        if (! $student->user_id) {
            return;
        }

        $user = User::find($student->user_id);

        if (! $user) {
            return;
        }

        // Un mismo usuario puede tener ficha en más de un colegio, porque students admite la
        // misma cédula una vez por colegio y users la exige única en todo el sistema. Si queda
        // otra ficha viva, borrar el usuario se la llevaría por el CASCADE de students.user_id.
        if (Student::where('user_id', $user->id)->exists()) {
            return;
        }

        try {
            $user->delete();
        } catch (\Throwable $th) {
            // Si algo todavía apunta a ese usuario (comments es RESTRICT), no se puede borrar.
            // En ese caso se desactiva y se le quita la cédula, que es lo que de verdad hacía
            // falta liberar; dejarlo activo con la cédula puesta bloquearía el alta de nuevo.
            Log::warning('No se pudo borrar el usuario del alumno; se desactiva y se libera la cédula', [
                'student_id' => $student->id,
                'user_id' => $user->id,
                'error' => $th->getMessage(),
            ]);

            $user->is_active = false;
            $user->identity_document = null;
            $user->saveQuietly();
        }
    }
}
