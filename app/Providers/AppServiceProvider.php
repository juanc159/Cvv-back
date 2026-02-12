<?php

namespace App\Providers;

use App\Interfaces\TodoInterface;
use App\Repositories\TodoRepository;
use App\Repositories\TodoRepositoryRedis;
use Illuminate\Support\ServiceProvider;
use App\Models\Student;
use App\Models\Teacher;
use App\Observers\StudentObserver;
use App\Observers\TeacherObserver;
use Laravel\Passport\Passport; // <--- IMPORTANTE: Agrega esto

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TodoInterface::class, TodoRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Carbon\Carbon::setLocale(config('app.locale'));

        // Registrar Observadores
        Student::observe(StudentObserver::class);
        Teacher::observe(TeacherObserver::class);

        // ----------------------------------------------------
        // CONFIGURACIÓN DE CADUCIDAD DE PASSPORT (2 HORAS)
        // ----------------------------------------------------
        
        // Esto hace que los tokens expiren 2 horas después de ser creados.
        Passport::tokensExpireIn(now()->addHours(2));
        Passport::refreshTokensExpireIn(now()->addHours(2));
        Passport::personalAccessTokensExpireIn(now()->addHours(2));
    }
}