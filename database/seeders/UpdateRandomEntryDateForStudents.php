<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student; 
use Faker\Factory as Faker;

class UpdateRandomEntryDateForStudents extends Seeder
{
    /**
     * Ejecutar la siembra de la base de datos.
     *
     * @return void
     */
    public function run()
    {
        // Instanciamos Faker para generar fechas aleatorias
        $faker = Faker::create();

        // Obtenemos todos los estudiantes
        $students = Student::all();

        foreach ($students as $student) {
            // Generamos una fecha aleatoria entre 2024-01-01 y 2025-01-01
            $randomDate = $faker->dateTimeBetween('2024-01-01', '2025-01-01')->format('Y-m-d');
            
            // Actualizamos el campo 'real_entry_date' con la fecha aleatoria
            $student->real_entry_date = $randomDate;
            $student->save();  // Guardamos los cambios
        }

        // Mensaje para confirmar que el seeder se ejecutó correctamente
        echo "Fechas de ingreso aleatorias actualizadas para los estudiantes.\n";
    }
}
