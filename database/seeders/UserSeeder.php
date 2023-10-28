<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisions = Permission::all()->pluck('id');

        $dataArray = [
            [
                "name" => "Administrador",
                "last_name" => "Principal",
                "company_id" => null,
                "email" => "admin@admin.com",
                "permissions" => $permisions,
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new User();
            $data->name = $value["name"];
            $data->last_name = $value["last_name"];
            $data->company_id = $value["company_id"];
            $data->email = $value["email"];
            $data->password = Hash::make(123456789);
            $data->save();
            $data->permissions()->sync($value["permissions"]);
        }
    }
}
