<?php

namespace Database\Seeders;

use App\Helpers\Constants;
use App\Models\JobPosition;
use Illuminate\Database\Seeder;

class JobPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataArray = [
            [
                'id' => Constants::MANAGERS_UUID,
                'name' => 'Director(a)',
            ],
            [
                'id' => Constants::COORDINATORS_UUID,
                'name' => 'Coordinador(a)',
            ],
            [
                'id' => Constants::TEACHERS_UUID,
                'name' => 'Profesor(a)',
            ],
            [
                'id' => Constants::SPECIALISTS_UUID,
                'name' => 'Especialista',
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new JobPosition;
            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->save();
        }
    }
}
