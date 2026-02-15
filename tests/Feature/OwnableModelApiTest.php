<?php

namespace Sowailem\Ownable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Sowailem\Ownable\Models\OwnableModel;
use Sowailem\Ownable\Tests\TestCase;

class OwnableModelApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::define('manage-ownable-models', fn () => true);
        Gate::define('create-ownable-model', fn () => true);
    }

    /** @test */
    public function it_can_create_an_ownable_model()
    {
        $response = $this->postJson('api/ownable/ownable-models', [
            'model_class' => 'App\Models\Post',
            'description' => 'Blog posts that can be owned',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.model_class', 'App\Models\Post');

        $this->assertDatabaseHas('ownable_models', [
            'model_class' => 'App\Models\Post',
            'description' => 'Blog posts that can be owned',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_list_ownable_models()
    {
        OwnableModel::create(['model_class' => 'App\Models\Post']);
        OwnableModel::create(['model_class' => 'App\Models\Comment']);

        $response = $this->getJson('api/ownable/ownable-models');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_show_an_ownable_model()
    {
        $model = OwnableModel::create(['model_class' => 'App\Models\Post']);

        $response = $this->getJson('api/ownable/ownable-models/' . $model->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.model_class', 'App\Models\Post');
    }

    /** @test */
    public function it_can_update_an_ownable_model()
    {
        $model = OwnableModel::create([
            'model_class' => 'App\Models\Post',
            'is_active' => true
        ]);

        $response = $this->putJson('api/ownable/ownable-models/' . $model->id, [
            'is_active' => false,
            'description' => 'Updated description'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ownable_models', [
            'id' => $model->id,
            'is_active' => false,
            'description' => 'Updated description'
        ]);
    }

    /** @test */
    public function it_can_delete_an_ownable_model()
    {
        $model = OwnableModel::create(['model_class' => 'App\Models\Post']);

        $response = $this->deleteJson('api/ownable/ownable-models/' . $model->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ownable_models', ['id' => $model->id]);
    }

    /** @test */
    public function it_validates_unique_model_class()
    {
        OwnableModel::create(['model_class' => 'App\Models\Post']);

        $response = $this->postJson('api/ownable/ownable-models', [
            'model_class' => 'App\Models\Post',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['model_class']);
    }
}
