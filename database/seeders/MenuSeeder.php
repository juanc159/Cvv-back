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
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'banner.list',
            ],
            [
                'id' => 7,
                'order' => 70,
                'title' => 'Materias',
                'to' => 'Subject-List',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'subject.list',
            ],
            [
                'id' => 8,
                'order' => 80,
                'title' => 'Grados',
                'to' => 'Grade-List',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'grade.list',
            ],
            [
                'id' => 9,
                'order' => 90,
                'title' => 'Servicios',
                'to' => 'Service-List',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'service.list',
            ],
            [
                'id' => 10,
                'order' => 100,
                'title' => 'Estudiantes',
                'to' => 'Student-List',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'student.list',
            ],
            [
                'id' => 11,
                'order' => 110,
                'title' => 'Docentes',
                'to' => 'Teacher-List',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'teacher.list',
            ],
            [
                'id' => 12,
                'order' => 120,
                'title' => 'Notas',
                'to' => 'Note-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'note.index',
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
