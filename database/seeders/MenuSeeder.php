<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrayData = [
            [
                'id' => 1,
                'order' => 10,
                'title' => 'Inicio',
                'to' => 'Home',
                'icon' => 'tabler-home',
                'requiredPermission' => 'menu.home',
            ],
            [
                'id' => 2,
                'order' => 20,
                'title' => 'Compañias',
                'to' => 'Company-List',
                'icon' => ' tabler-building',
                'requiredPermission' => 'company.list',
            ],
            [
                'id' => 3,
                'order' => 30,
                'title' => 'Usuarios',
                'icon' => 'tabler-user-shield',
                'requiredPermission' => 'menu.user.father',
            ],
            [
                'id' => 4,
                'order' => 40,
                'title' => 'Usuarios',
                'to' => 'User-List',
                'icon' => '',
                'father' => 3,
                'requiredPermission' => 'menu.user',
            ],
            [
                'id' => 5,
                'order' => 50,
                'title' => 'Roles',
                'to' => 'Role-List',
                'icon' => '',
                'father' => 3,
                'requiredPermission' => 'menu.role',
            ],
            [
                'id' => 6,
                'order' => 60,
                'title' => 'Banner',
                'to' => 'Banner-List',
                'icon' => 'tabler-border-inner',
                'requiredPermission' => 'banner.list',
            ],
            [
                'id' => 7,
                'order' => 70,
                'title' => 'Materias',
                'to' => 'Subject-List',
                'icon' => 'tabler-checklist',
                'requiredPermission' => 'subject.list',
            ],
            [
                'id' => 8,
                'order' => 80,
                'title' => 'Grados',
                'to' => 'Grade-List',
                'icon' => 'tabler-layers-intersect',
                'requiredPermission' => 'grade.list',
            ],
            [
                'id' => 9,
                'order' => 90,
                'title' => 'Servicios',
                'to' => 'Service-List',
                'icon' => 'tabler-device-gamepad-3',
                'requiredPermission' => 'service.list',
            ],
            [
                'id' => 10,
                'order' => 100,
                'title' => 'Estudiantes',
                'to' => 'Student-List',
                'icon' => 'tabler-users-group',
                'requiredPermission' => 'student.list',
            ],
            [
                'id' => 11,
                'order' => 110,
                'title' => 'Docentes',
                'to' => 'Teacher-List',
                'icon' => 'tabler-user-square-rounded',
                'requiredPermission' => 'teacher.list',
            ],
            [
                'id' => 12,
                'order' => 120,
                'title' => 'Notas',
                'to' => 'Note-Index',
                'icon' => 'tabler-database-edit',
                'requiredPermission' => 'note.index',
            ],
            [
                'id' => 13,
                'order' => 130,
                'title' => 'Ingresos/Egresos',
                'to' => 'Enrollments&Exits-Index',
                'icon' => 'tabler-database-edit',
                'requiredPermission' => 'menu.enrollments&Exits',
            ],
            [
                'id' => 14,
                'order' => 140,
                'title' => 'Periodos',
                'to' => 'Term-List',
                'icon' => 'tabler-calendar',
                'requiredPermission' => 'term.list',
            ],
            [
                'id' => 15,
                'order' => 150,
                'title' => 'Materia Pendiente',
                'icon' => 'tabler-parking',
                'requiredPermission' => 'menu.pendingRegistration.father',
            ],
            [
                'id' => 16,
                'order' => 160,
                'title' => 'Materia Pendiente',
                'to' => 'PendingRegistration-List',
                'icon' => 'tabler-parking',
                'requiredPermission' => 'pendingRegistration.list',
                'father' => 15,

            ],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        foreach ($arrayData as $key => $value) {
            $data = Menu::find($value['id']);
            if (! $data) {
                $data = new Menu;
            }
            $data->id = $value['id'];
            $data->order = $value['order'];
            $data->title = $value['title'];
            $data->to = $value['to'] ?? null;
            $data->icon = $value['icon'];
            $data->father = $value['father'] ?? null;
            $data->requiredPermission = $value['requiredPermission'];
            $data->save();
        }

        $bar->finish(); // Finalizar la barra
    }
}
