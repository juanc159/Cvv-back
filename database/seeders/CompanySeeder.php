<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $dataArray = [
            [
                'name' => 'U.E Colegio virgen del valle',
                'slogan' => null,
            ],
            [
                'name' => 'Virgen del Valle International School',
                'slogan' => null,
            ],
            [
                'name' => 'U.E Colegio Lolita Robles de Mora',
                'slogan' => null,
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Company();
            $data->name = $value['name'];
            $data->slogan = $value['slogan'];
            $data->save();
        }
    }
}
