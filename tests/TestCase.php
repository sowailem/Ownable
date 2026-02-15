<?php

namespace Sowailem\Ownable\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Sowailem\Ownable\OwnableServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Sowailem\\Ownable\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            OwnableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => '3306',
            'database' => 'ownable_test',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'   => '',
        ]);
        
        // Set the owner model to use test User model
        config()->set('ownable.owner_model', 'Sowailem\\Ownable\\Tests\\Models\\User');
        
        // Set the ownable models
        config()->set('ownable.ownable_models', ['Sowailem\\Ownable\\Tests\\Models\\Post']);
    }

    protected function setUpDatabase()
    {
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create test users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        // Create test posts table for ownable objects
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }
}