<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            $this->mapApiRoutes();
            $this->mapWebRoutes();
        });
    }

    protected function mapWebRoutes()
    {
        foreach ($this->centralDomains() as $domain) {
            Route::middleware('web')
                ->domain($domain)
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        }
    }

    protected function mapApiRoutes()
    {
        // esto es para las apis que no requieran auth
        $routesApi = [
            'routes/api.php',
            'routes/authentication.php',
            'routes/pw.php',
        ];
        foreach ($this->centralDomains() as $domain) {
            foreach ($routesApi as $route) {
                Route::prefix('api')
                    ->domain($domain)
                    ->middleware('api')
                    // ->middleware(['api',InitializeTenancyByDomain::class,PreventAccessFromCentralDomains::class,])
                    ->namespace($this->namespace)
                    ->group(base_path($route));
            }
        }

        // esto es para las apis que si requieran auth
        $routesAuthApi = [
            'routes/Querys/query.php',
            'routes/user.php',
            'routes/company.php',
            'routes/banner.php',
            'routes/subject.php',
            'routes/teacher.php',
            'routes/note.php',
            'routes/grade.php',
            'routes/service.php',
            'routes/student.php',
            'routes/jobPosition.php',
        ];

        //este es para el o los centrales
        foreach ($this->centralDomains() as $domain) {
            foreach ($routesAuthApi as $route) {
                Route::prefix('api')
                    ->domain($domain)
                    ->middleware('auth:api')
                    // ->middleware(['api',InitializeTenancyByDomain::class,PreventAccessFromCentralDomains::class,])
                    ->namespace($this->namespace)
                    ->group(base_path($route));
            }
        }

        //esto es para los dominios no centrales osea todos los paises
        foreach ($routesAuthApi as $route) {
            Route::prefix('api')
                ->middleware(['auth:api', InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class])
                ->namespace($this->namespace)
                ->group(base_path($route));
        }
    }

    protected function centralDomains(): array
    {
        return config('tenancy.central_domains');
    }
}
