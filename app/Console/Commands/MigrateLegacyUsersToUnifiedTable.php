<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateLegacyUsersToUnifiedTable extends Command
{
    /**
     * El nombre y firma del comando.
     */
    protected $signature = 'migrate:unified-users';

    /**
     * La descripción del comando.
     */
    protected $description = 'Mueve estudiantes y docentes a la tabla users unificada';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $this->info('Iniciando migración de usuarios...');

        DB::beginTransaction();

        try {
            // ---------------------------------------------------------
            // 1. MIGRAR DOCENTES
            // ---------------------------------------------------------
            $teachers = Teacher::whereNull('user_id')->get(); // Solo los que no se han migrado
            $this->info("Encontrados {$teachers->count()} docentes para migrar.");

            foreach ($teachers as $teacher) {
                // Verificar si el email ya existe en users (para evitar duplicados)
                $existingUser = User::where('email', $teacher->email)->first();

                if ($existingUser) {
                    $this->warn("El docente {$teacher->email} ya existe en users. Vinculando...");
                    $teacher->user_id = $existingUser->id;
                    $teacher->save();
                    continue;
                }

                // Crear el Usuario Maestro
                $newUser = User::create([
                    'name' => $teacher->name,
                    'surname' => $teacher->last_name, // Ojo: en teachers se llama last_name
                    'email' => $teacher->email,
                    'password' => $teacher->password, // Copiamos el hash exacto
                    'type_user' => 'teacher',
                    'is_active' => $teacher->is_active,
                    'company_id' => $teacher->company_id,
                    'email_verified_at' => now(),
                ]);

                // Guardar la referencia en la tabla teachers
                $teacher->user_id = $newUser->id;
                $teacher->save();
            }
            
            $this->info('Docentes migrados correctamente.');

            // ---------------------------------------------------------
            // 2. MIGRAR ESTUDIANTES
            // ---------------------------------------------------------
            $students = Student::whereNull('user_id')->get();
            $this->info("Encontrados {$students->count()} estudiantes para migrar.");

            foreach ($students as $student) {
                // Verificar duplicados por cédula
                $existingUser = User::where('identity_document', $student->identity_document)->first();

                if ($existingUser) {
                    $this->warn("El estudiante {$student->identity_document} ya existe. Vinculando...");
                    $student->user_id = $existingUser->id;
                    $student->save();
                    continue;
                }

                // Separar Nombre y Apellido (lógica básica)
                $parts = explode(' ', $student->full_name);
                $name = $parts[0];
                $surname = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';

                // Crear el Usuario Maestro
                $newUser = User::create([
                    'name' => $name,
                    'surname' => $surname,
                    'identity_document' => $student->identity_document,
                    'password' => $student->password, // Copiamos el hash exacto
                    'type_user' => 'student',
                    'is_active' => $student->is_active,
                    'company_id' => $student->company_id,
                    'email' => null, // El email queda null para estudiantes por ahora
                ]);

                // Guardar la referencia
                $student->user_id = $newUser->id;
                $student->save();
            }

            $this->info('Estudiantes migrados correctamente.');
            
            DB::commit();
            $this->info('¡MIGRACIÓN COMPLETADA CON ÉXITO! 🚀');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ocurrió un error: ' . $e->getMessage());
        }
    }
}