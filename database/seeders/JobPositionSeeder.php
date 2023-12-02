<?php

namespace Database\Seeders;

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
                'id' => 1,
                'name' => 'Director(a)',
            ],
            [
                'id' => 2,
                'name' => 'Coordinador(a)',
            ],
            [
                'id' => 3,
                'name' => 'Profesor(a)',
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new JobPosition();
            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->save();
        }
    }
}
