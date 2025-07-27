<?php

namespace Database\Seeders;

use App\Helpers\Constants;
use App\Models\TypeEducation;
use Illuminate\Database\Seeder;

class TypeEducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrayData = [
            [
                'id' => Constants::INITIAL_EDUCATION_UUID,
                'name' => 'Educación Inicial',
                'cantNotes' => 1,
            ],
            [
                'id' => Constants::PRIMARY_EDUCATION_UUID,
                'name' => 'Educación Primaria',
                'cantNotes' => 6,
            ],
            [
                'id' => Constants::GENERAL_SECONDARY_EDUCATION_UUID,
                'name' => 'Educación Media General',
                'cantNotes' => 3,
            ],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        foreach ($arrayData as $key => $value) {
            $data = new TypeEducation;
            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->cantNotes = $value['cantNotes'];
            $data->save();
        }
        $bar->finish(); // Finalizar la barra

    }
}
