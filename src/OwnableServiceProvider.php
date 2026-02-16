<?php

namespace Sowailem\Ownable;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
//use Illuminate\Contracts\Http\Kernel;
//use Sowailem\Ownable\Http\Middleware\AttachOwnershipMiddleware;

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
            return new \Sowailem\Ownable\Owner($app->make(\Sowailem\Ownable\Services\OwnershipService::class));
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

        $this->registerMiddleware();

        Blade::if('owns', function ($owner, $ownable) {
            return app('ownable.owner')->check($owner, $ownable);
        });

        $this->registerMacros();
    }

    /**
     * Register the dynamic macros for ownable models.
     *
     * @return void
     */
    protected function registerMacros(): void
    {
        $ownableModels = (array) config('ownable.ownable_models', []);

        // Load models from database if table exists
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('ownable_models')) {
                $dbModels = \Sowailem\Ownable\Models\OwnableModel::where('is_active', true)
                    ->pluck('model_class')
                    ->toArray();
                $ownableModels = array_unique(array_merge($ownableModels, $dbModels));
            }
        } catch (\Exception $e) {
            // Silently fail if a database is not reachable or other issues
        }

        $ownerModels = config('ownable.owner_models',[]);
        $macroName = config('ownable.macro_name', 'owner');

        $ownerModels = array_unique(array_merge($ownerModels, $ownableModels));
        // Register macro for owner models to access owned items
        foreach ($ownerModels as $ownerModel) {
            if (class_exists($ownerModel)) {
                \Illuminate\Database\Eloquent\Model::resolveRelationUsing('ownedItems', function ($thisModel) use ($ownerModel) {
                    if ($thisModel instanceof $ownerModel) {
                        return $thisModel->hasMany(\Sowailem\Ownable\Models\Ownership::class, 'owner_id', $thisModel->getKeyName())
                            ->where('owner_type', $thisModel->getMorphClass())
                            ->where('is_current', true)
                            ->with('ownable');
                    }
                    return null;
                });
            }
        }

        foreach ($ownableModels as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::macro($macroName, function () use ($ownerModel) {
                    /** @var \Illuminate\Database\Eloquent\Model $this */
                    return $this->morphToMany($ownerModel, 'ownable', 'ownerships', 'ownable_id', 'owner_id')
                        ->wherePivot('is_current', true)
                        ->withPivot('is_current')
                        ->withTimestamps();
                });

                // Also register a macro for ownership history
                $modelClass::macro($macroName . 'History', function () use ($ownerModel) {
                    /** @var \Illuminate\Database\Eloquent\Model $this */
                    return $this->morphToMany($ownerModel, 'ownable', 'ownerships', 'ownable_id', 'owner_id')
                        ->withPivot('is_current')
                        ->withTimestamps();
                });
            }
        }
    }

    /**
     * Register the package middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
//        $kernel = $this->app->make(Kernel::class);
//        $kernel->pushMiddleware(AttachOwnershipMiddleware::class);
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
            Route::get('ownerships', \Sowailem\Ownable\Http\Controllers\Ownership\ListOwnershipController::class);
            Route::post('ownerships/give', \Sowailem\Ownable\Http\Controllers\Ownership\GiveOwnershipController::class);
            Route::post('ownerships/transfer', \Sowailem\Ownable\Http\Controllers\Ownership\TransferOwnershipController::class);
            Route::post('ownerships/check', \Sowailem\Ownable\Http\Controllers\Ownership\CheckOwnershipController::class);
            Route::post('ownerships/remove', \Sowailem\Ownable\Http\Controllers\Ownership\RemoveOwnershipController::class);
            Route::post('ownerships/current', \Sowailem\Ownable\Http\Controllers\Ownership\GetCurrentOwnerController::class);

            Route::get('ownable-models', \Sowailem\Ownable\Http\Controllers\OwnableModel\ListOwnableModelController::class);
            Route::post('ownable-models', \Sowailem\Ownable\Http\Controllers\OwnableModel\CreateOwnableModelController::class);
            Route::get('ownable-models/{ownable_model}', \Sowailem\Ownable\Http\Controllers\OwnableModel\ViewOwnableModelController::class);
            Route::put('ownable-models/{ownable_model}', \Sowailem\Ownable\Http\Controllers\OwnableModel\UpdateOwnableModelController::class);
            Route::patch('ownable-models/{ownable_model}', \Sowailem\Ownable\Http\Controllers\OwnableModel\UpdateOwnableModelController::class);
            Route::delete('ownable-models/{ownable_model}', \Sowailem\Ownable\Http\Controllers\OwnableModel\DeleteOwnableModelController::class);
        });
    }
}