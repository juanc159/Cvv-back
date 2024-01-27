<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $array = [
            [
                'id' => 1,
                'name' => 'dashboard.index',
                'description' => 'Visualizar Módulo Dashboard',
                'menu_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'company.index',
                'description' => 'Visualizar Módulo Companias',
                'menu_id' => 2,
            ],
            [
                'id' => 3,
                'name' => 'users.index',
                'description' => 'Visualizar Módulo Usuarios',
                'menu_id' => 3,
            ],
            [
                'id' => 4,
                'name' => 'roles.index',
                'description' => 'Visualizar Módulo Roles',
                'menu_id' => 4,
            ],
            [
                'id' => 5,
                'name' => 'banner.index',
                'description' => 'Visualizar Módulo Banner',
                'menu_id' => 5,
            ],
            [
                'id' => 6,
                'name' => 'subject.index',
                'description' => 'Visualizar Módulo Materia',
                'menu_id' => 6,
            ],
            [
                'id' => 7,
                'name' => 'teacher.index',
                'description' => 'Visualizar Módulo Docente',
                'menu_id' => 7,
            ],
            [
                'id' => 8,
                'name' => 'note.index',
                'description' => 'Visualizar Módulo Notas',
                'menu_id' => 8,
            ],
            [
                'id' => 9,
                'name' => 'grade.index',
                'description' => 'Visualizar Módulo Grados',
                'menu_id' => 9,
            ],
        ];

        foreach ($array as $key => $value) {
            $data =  Permission::find($value["id"]);
            if(!$data){
                $data = new Permission();
            }

            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->description = $value['description'];
            $data->menu_id = $value['menu_id'];
            $data->save();
        }
    }
}
