<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataArray = [
            [
                "id" => 1,
                "name" => "General",
                "code" => "GEN",
            ],
            [
                "id" => 2,
                "name" => "Matematicas",
                "code" => "MAT",
            ],
            [
                "id" => 3,
                "name" => "Química",
                "code" => "QUI",
            ],
            [
                "id" => 4,
                "name" => "Física",
                "code" => "FIS",
            ],
            [
                "id" => 5,
                "name" => "Deportes",
                "code" => "DEP",
            ],
            [
                "id" => 6,
                "name" => "Informática",
                "code" => "INF",
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Subject();
            $data->id = $value["id"];
            $data->name = $value["name"];
            $data->code = $value["code"];
            $data->save();
        }
    }
}
