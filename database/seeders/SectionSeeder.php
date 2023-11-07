<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataArray = [
            [
                "id" => 1,
                "name" => "A",
            ],
            [
                "id" => 2,
                "name" => "B",
            ],
            [
                "id" => 3,
                "name" => "C",
            ],
            [
                "id" => 4,
                "name" => "D",
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Section();
            $data->id = $value["id"];
            $data->name = $value["name"];
            $data->save();
        }
    }
}
