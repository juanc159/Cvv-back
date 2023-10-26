<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Passport 11.x
        // Route::group([
        //     'as' => 'passport.',
        //     'middleware' => [
        //         'universal',
        //         InitializeTenancyByDomain::class
        //     ],
        //     'prefix' => config('passport.path', 'oauth'),
        //     'namespace' => 'Laravel\Passport\Http\Controllers',
        // ], function () {
        //    echo  $this->loadRoutesFrom(__DIR__ . "/../../vendor/laravel/passport/src/../routes/web.php");
        // });
    }
}
