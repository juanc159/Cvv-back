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
                'id' => 1,
                'name' => 'General',
                'code' => 'GEN',
                'type_education_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Matematicas',
                'code' => 'MAT',
                'type_education_id' => 3,
            ],
            [
                'id' => 3,
                'name' => 'Química',
                'code' => 'QUI',
                'type_education_id' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Física',
                'code' => 'FIS',
                'type_education_id' => 3,
            ],
            [
                'id' => 5,
                'name' => 'Deportes',
                'code' => 'DEP',
                'type_education_id' => 3,
            ],
            [
                'id' => 6,
                'name' => 'Informática',
                'code' => 'INF',
                'type_education_id' => 3,
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Subject();
            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->code = $value['code'];
            $data->type_education_id = $value['type_education_id'];
            $data->save();
        }
    }
}
