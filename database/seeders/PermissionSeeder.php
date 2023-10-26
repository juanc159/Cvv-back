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
                'name' => 'tenants.index',
                'description' => 'Visualizar Módulo Tenant',
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
            ]
        ];

        foreach ($array as $key => $value) {
            $data = new Permission();
            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->description = $value['description'];
            $data->menu_id = $value['menu_id'];
            $data->save();
        }
    }
}
