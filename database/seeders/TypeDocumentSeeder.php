<?php

namespace Database\Seeders;

use App\Models\TypeDocument;
use Illuminate\Database\Seeder;

class TypeDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataArray = [
            [
                'name' => 'Cédula de identidad',
            ],
            [
                'name' => 'Cédula escolar',
            ],
            [
                'name' => 'Número de pasaporte',
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new TypeDocument();
            $data->name = $value['name'];
            $data->save();
        }
    }
}
