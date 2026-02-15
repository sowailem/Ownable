<?php

namespace Sowailem\Ownable;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Ownable package.
 * 
 * This service provider handles the registration and bootstrapping of the
 * Ownable package, including configuration merging, service binding,
 * migration loading, and asset publishing.
 */
class OwnableServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * 
     * This method merges the package configuration and binds the Owner
     * service as a singleton in the service container.
     * 
     * @return void
     */
    public function register(): void
    {
        $configPath = dirname(__DIR__).'/config/ownable.php';

        if ($this->app->bound('config') && file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'ownable');
        }

        $this->app->singleton('ownable.owner', function ($app) {
            return new \Sowailem\Ownable\Owner();
        });
    }

    /**
     * Bootstrap the service provider.
     * 
     * This method loads migrations and publishes configuration and
     * migration files for the package.
     * 
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/ownable.php' => config_path('ownable.php'),
            ], 'ownable-config');
        }

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');

        $this->registerRoutes();

        Blade::if('owns', function ($owner, $ownable) {
            return app('ownable.owner')->check($owner, $ownable);
        });
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('ownable.routes.prefix', 'api/ownable'),
            'middleware' => config('ownable.routes.middleware', ['api']),
        ], function () {
            Route::post('ownerships', [\Sowailem\Ownable\Http\Controllers\OwnershipController::class, 'store']);
            Route::get('ownerships', [\Sowailem\Ownable\Http\Controllers\OwnershipController::class, 'index']);
        });
    }
}