<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataArray = [
            [
                'id' => 1,
                'company_id' => 1,
                'type_education_id' => 1,
                'name' => 'Primer Nivel',
                'state' => 1,
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'type_education_id' => 1,
                'name' => 'Segundo Nivel',
                'state' => 1,
            ],
            [
                'id' => 3,
                'company_id' => 1,
                'type_education_id' => 1,
                'name' => 'Tercer Nivel',
                'state' => 1,
            ],
            [
                'id' => 4,
                'company_id' => 1,
                'type_education_id' => 2,
                'name' => '1ER GRADO',
                'state' => 1,
            ],
            [
                'id' => 5,
                'company_id' => 1,
                'type_education_id' => 2,
                'name' => '2DO GRADO',
                'state' => 1,
            ],
            [
                'id' => 6,
                'company_id' => 1,
                'type_education_id' => 2,
                'name' => '3ER GRADO',
                'state' => 1,
            ],
            [
                'id' => 7,
                'company_id' => 1,
                'type_education_id' => 2,
                'name' => '4TO GRADO',
                'state' => 1,
            ],
            [
                'id' => 8,
                'company_id' => 1,
                'type_education_id' => 2,
                'name' => '5TO GRADO',
                'state' => 1,
            ],
            [
                'id' => 9,
                'company_id' => 1,
                'type_education_id' => 2,
                'name' => '6TO GRADO',
                'state' => 1,
            ],
            [
                'id' => 10,
                'company_id' => 1,
                'type_education_id' => 3,
                'name' => '1ER AÑO',
                'state' => 1,
            ],
            [
                'id' => 11,
                'company_id' => 1,
                'type_education_id' => 3,
                'name' => '2DO AÑO',
                'state' => 1,
            ],
            [
                'id' => 12,
                'company_id' => 1,
                'type_education_id' => 3,
                'name' => '3ER AÑO',
                'state' => 1,
            ],
            [
                'id' => 13,
                'company_id' => 1,
                'type_education_id' => 3,
                'name' => '4TO AÑO',
                'state' => 1,
            ],
            [
                'id' => 14,
                'company_id' => 1,
                'type_education_id' => 3,
                'name' => '5TO AÑO',
                'state' => 1,
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Grade();
            $data->id = $value['id'];
            $data->company_id = $value['company_id'];
            $data->type_education_id = $value['type_education_id'];
            $data->name = $value['name'];
            $data->state = $value['state'];
            $data->save();
        }
    }
}
