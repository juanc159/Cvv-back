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
                'title' => 'Dashboard',
                'to' => 'root',
                'icon' => 'tabler-smart-home',
                'requiredPermission' => 'dashboard.index',
            ],
            [
                'id' => 2,
                'title' => 'Compañias',
                'to' => 'Company-Index',
                'icon' => 'flag-checkered',
                'requiredPermission' => 'company.index',
            ],
            [
                'id' => 3,
                'title' => 'Usuarios',
                'to' => 'Users-Index',
                'icon' => 'tabler-users',
                'requiredPermission' => 'users.index',
            ],
            [
                'id' => 4,
                'title' => 'Roles',
                'to' => 'Roles-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'roles.index',
            ],
            [
                'id' => 5,
                'title' => 'Banner',
                'to' => 'Banner-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'banner.index',
            ],
            [
                'id' => 6,
                'title' => 'Materias',
                'to' => 'Subject-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'subject.index',
            ],
            [
                'id' => 7,
                'title' => 'Docente',
                'to' => 'Teacher-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'teacher.index',
            ],
            [
                'id' => 8,
                'title' => 'Subir Notas',
                'to' => 'Note-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'note.index',
            ],
            [
                'id' => 9,
                'title' => 'Grados',
                'to' => 'Grade-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'grade.index',
            ],
            [
                'id' => 10,
                'title' => 'Servicios',
                'to' => 'Service-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'service.index',
            ],
            [
                'id' => 11,
                'title' => 'Estudiantes',
                'to' => 'Student-Index',
                'icon' => 'mdi-account-lock-outline',
                'requiredPermission' => 'student.index',
            ],
        ];
        foreach ($arrayData as $key => $value) {
            $data =  Menu::find($value["id"]);
            if(!$data){
                $data = new Menu();
            }
            $data->id = $value['id'];
            $data->title = $value['title'];
            $data->to = $value['to'] ?? null;
            $data->icon = $value['icon'];
            $data->father = $value['father'] ?? null;
            $data->requiredPermission = $value['requiredPermission'];
            $data->save();
        }
    }
}
