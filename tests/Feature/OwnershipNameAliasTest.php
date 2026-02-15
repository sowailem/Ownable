<?php

namespace Sowailem\Ownable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Models\OwnableModel;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class OwnershipNameAliasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('test-alias-post/{post}', function ($post_id) {
            $post = Post::find($post_id);
            return response()->json($post);
        });
    }

    /** @test */
    public function it_uses_the_unique_name_alias_in_json_response()
    {
        // 1. Register Post model with a unique name
        OwnableModel::create([
            'name' => 'MyPostAlias',
            'model_class' => Post::class,
            'is_active' => true,
        ]);

        // 2. Also register User model (owner) with a name
        OwnableModel::create([
            'name' => 'MyUserAlias',
            'model_class' => User::class,
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->getJson("test-alias-post/{$post->id}");

        $response->assertStatus(200);
        
        // Check that ownable_type is the alias, not the FQN
        $response->assertJsonPath('ownership.ownable_type', 'MyPostAlias');
        
        // Check that owner_type is also the alias
        $response->assertJsonPath('ownership.owner_type', 'MyUserAlias');
    }

    /** @test */
    public function it_falls_back_to_class_basename_if_not_in_ownable_models()
    {
        // Don't register anything in ownable_models (or at least not User)
        
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->getJson("test-alias-post/{$post->id}");

        $response->assertStatus(200);
        
        // Post is in config 'ownable.ownable_models' in TestCase, so it might use basename there if not in DB
        // Let's check what it uses.
        $response->assertJsonPath('ownership.ownable_type', 'Post');
        $response->assertJsonPath('ownership.owner_type', 'User');
    }
}
