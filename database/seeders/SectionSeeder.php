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
                'name' => 'A',
            ],
            [
                'name' => 'B',
            ],
            [
                'name' => 'C',
            ],
            [
                'name' => 'D',
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Section;
            // $data->id = $value['id'];
            $data->name = $value['name'];
            $data->save();
        }
    }
}
