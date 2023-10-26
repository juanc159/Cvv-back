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
                'title' => 'Tenants',
                'to' => 'Tenants-Index',
                'icon' => 'flag-checkered',
                'requiredPermission' => 'tenants.index',
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
            ]
        ];
        foreach ($arrayData as $key => $value) {
            $data = new Menu();
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
