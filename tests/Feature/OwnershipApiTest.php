<?php

namespace Sowailem\Ownable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;

class OwnershipApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::define('view-ownerships', fn () => true);
        Gate::define('create-ownership', fn () => true);
    }

    /** @test */
    public function it_can_give_ownership_via_api()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        $response = $this->postJson('api/ownable/ownerships/give', [
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.owner_id', $user->id);
        $response->assertJsonPath('data.ownable_id', $post->id);

        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_updates_current_ownership_when_giving_new_one()
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        // Register first ownership
        Ownership::create([
            'owner_id' => $user1->id,
            'owner_type' => get_class($user1),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        // Register new ownership via API
        $response = $this->postJson('api/ownable/ownerships/give', [
            'owner_id' => $user2->id,
            'owner_type' => get_class($user2),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user1->id,
            'ownable_id' => $post->id,
            'is_current' => false,
        ]);

        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user2->id,
            'ownable_id' => $post->id,
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_can_transfer_ownership_via_api()
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        // Register first ownership
        Ownership::create([
            'owner_id' => $user1->id,
            'owner_type' => get_class($user1),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->postJson('api/ownable/ownerships/transfer', [
            'from_owner_id' => $user1->id,
            'from_owner_type' => get_class($user1),
            'to_owner_id' => $user2->id,
            'to_owner_type' => get_class($user2),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user2->id,
            'ownable_id' => $post->id,
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_can_check_ownership_via_api()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->postJson('api/ownable/ownerships/check', [
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['owns' => true]);
    }

    /** @test */
    public function it_can_remove_ownership_via_api()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->postJson('api/ownable/ownerships/remove', [
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ownerships', [
            'ownable_id' => $post->id,
            'is_current' => false,
        ]);
    }

    /** @test */
    public function it_can_get_current_owner_via_api()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->postJson('api/ownable/ownerships/current', [
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $user->id);
    }

    /** @test */
    public function it_can_list_ownerships_via_api()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->getJson('api/ownable/ownerships');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
