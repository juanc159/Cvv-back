<?php

namespace App\Providers;

use App\Interfaces\TodoInterface;
use App\Repositories\TodoRepository;
use App\Repositories\TodoRepositoryRedis;
use Illuminate\Support\ServiceProvider;

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
    }
}
