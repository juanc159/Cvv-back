<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            TypeDetailSeeder::class,
            CompanySeeder::class,
            CompanyDetailSeeder::class,
            MenuSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            // CountrySeeder::class,
            // DepartmentSeeder::class,
            // CitySeeder::class,

            JobPositionSeeder::class,
            TypeEducationSeeder::class,
            GradeSeeder::class,
            SectionSeeder::class,
            // SubjectSeeder::class,
        ]);

        // Teacher::factory(20)->create();

        $client = new ClientRepository();

        $client->createPasswordGrantClient(null, 'Laravel Personal Access Client', 'http://localhost');
        $client->createPersonalAccessClient(null, 'Laravel Password Grant Client', 'http://localhost');
    }
}
