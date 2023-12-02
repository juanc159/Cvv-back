<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Genera un nombre de archivo único
        $filename = Str::random(10).'.jpg';

        // Genera la imagen y guárdala en la carpeta de almacenamiento
        Storage::put('public/fake_images/'.$filename, file_get_contents($this->faker->image()));

        return [
            'company_id' => 1,
            'type_education_id' => random_int(1, 3),
            'job_position_id' => 3,
            'name' => fake()->unique()->name(),
            'last_name' => fake()->unique()->lastName(),
            'email' => fake()->unique()->email(),
            'phone' => fake()->unique()->phoneNumber(),
            // "photo" => fake()->unique()->image(),
            'photo' => request()->root().':8000'.'/storage/fake_images/'.$filename, // Ruta relativa a la carpeta storage
        ];
    }
}
