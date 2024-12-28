<?php

namespace Database\Seeders;

use App\Helpers\Constants;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos para insertar o actualizar
        $arrayData = [
            [
                'id' => 1,
                'name' => 'menu.home',
                'description' => 'Visualizar Menú Inicio',
                'menu_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'company.list',
                'description' => 'Visualizar Módulo de Compañia',
                'menu_id' => 2,
            ],
            [
                'id' => 3,
                'name' => 'menu.user.father',
                'description' => 'Visualizar Menú Acceso de usuarios',
                'menu_id' => 3,
            ],
            [
                'id' => 4,
                'name' => 'menu.user',
                'description' => 'Visualizar Menú Usuarios',
                'menu_id' => 4,
            ],
            [
                'id' => 5,
                'name' => 'menu.role',
                'description' => 'Visualizar Menú Roles',
                'menu_id' => 5,
            ],
            [
                'id' => 6,
                'name' => 'banner.list',
                'description' => 'Visualizar Módulo Banner',
                'menu_id' => 6,
            ],
            [
                'id' => 7,
                'name' => 'subject.list',
                'description' => 'Visualizar Módulo Materia',
                'menu_id' => 7,
            ],
            [
                'id' => 8,
                'name' => 'grade.list',
                'description' => 'Visualizar Módulo Grados',
                'menu_id' => 8,
            ],
            [
                'id' => 9,
                'name' => 'service.list',
                'description' => 'Visualizar Módulo Servicios',
                'menu_id' => 9,
            ],
            [
                'id' => 10,
                'name' => 'student.list',
                'description' => 'Visualizar Módulo Estudiantes',
                'menu_id' => 10,
            ],
            [
                'id' => 11,
                'name' => 'teacher.list',
                'description' => 'Visualizar Módulo Docentes',
                'menu_id' => 11,
            ],
            [
                'id' => 12,
                'name' => 'note.index',
                'description' => 'Visualizar Módulo Notas',
                'menu_id' => 12,
            ],
            [
                'id' => 13,
                'name' => 'note.upload_notes',
                'description' => 'Cargar Notas',
                'menu_id' => 12,
            ],
            [
                'id' => 14,
                'name' => 'note.download_notes',
                'description' => 'Descargar Notas',
                'menu_id' => 12,
            ],
            [
                'id' => 15,
                'name' => 'note.block_uploading_of_grades_to_teachers',
                'description' => 'Bloquear carga de notas a los profesores',
                'menu_id' => 12,
            ],
            [
                'id' => 16,
                'name' => 'note.viewing_notes',
                'description' => 'Visualización de las notas',
                'menu_id' => 12,
            ],
            [
                'id' => 17,
                'name' => 'note.bulk_file_upload',
                'description' => 'Carga de archivos masivos',
                'menu_id' => 12,
            ],
            [
                'id' => 18,
                'name' => 'note.reset_option_download_pdf',
                'description' => 'Reiniciar opción descarga pdf y boletin',
                'menu_id' => 12,
            ],
            [
                'id' => 19,
                'name' => 'teacher.reset_planifications',
                'description' => 'Reiniciar planificaciones',
                'menu_id' => 11,
            ],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        // Insertar o actualizar permisos
        foreach ($arrayData as $value) {
            Permission::updateOrCreate(
                ['id' => $value['id']],
                [
                    'name' => $value['name'],
                    'description' => $value['description'],
                    'menu_id' => $value['menu_id'],
                    'guard_name' => 'api',
                ]
            );
        }

        // Obtener permisos
        $permissions = Permission::whereIn('id', collect($arrayData)->pluck('id'))->get();

        // Asignar permisos al rol
        $role = Role::find(Constants::ROLE_SUPERADMIN_UUID);
        if ($role) {
            $role->syncPermissions($permissions);
        }

        // Sincronizar roles con usuarios
        $users = User::where('role_id', Constants::ROLE_SUPERADMIN_UUID)->get();
        foreach ($users as $user) {
            $role = Role::find($user->role_id);
            if ($role) {
                $user->syncRoles($role);
            }
        }

        $bar->finish(); // Finalizar la barra

    }
}
