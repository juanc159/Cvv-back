<?php

namespace Database\Seeders;

use App\Models\TypeEducation;
use Illuminate\Database\Seeder;

class TypeEducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataArray = [
            [
                "id" => 1,
                "name" => "Educación Inicial",
            ],
            [
                "id" => 2,
                "name" => "Educación Primaria",
            ],
            [
                "id" => 3,
                "name" => "Educación Media General",
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new TypeEducation();
            $data->id = $value["id"];
            $data->name = $value["name"];
            $data->save();
        }
    }
}
