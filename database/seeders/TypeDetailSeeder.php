<?php

namespace Database\Seeders;

use App\Models\TypeDetail;
use Illuminate\Database\Seeder;

class TypeDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $dataArray = [
            [
                'name' => 'Facebook',
            ],
            [
                'name' => 'instagram',
            ],
            [
                'name' => 'tiktok',
            ],
            [
                'name' => 'twiter',
            ],
            [
                'name' => 'youtube',
            ],
            [
                'name' => 'Dirección',
            ],
            [
                'name' => 'Teléfonos',
            ],
            [
                'name' => 'Correos',
            ],
            [
                'name' => 'Iframe GoogleMap',
            ],

        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($dataArray));

        foreach ($dataArray as $key => $value) {
            $data = new TypeDetail;
            $data->name = $value['name'];
            $data->save();
        }

        $bar->finish(); // Finalizar la barra
    }
}
