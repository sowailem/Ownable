<?php

namespace Sowailem\Ownable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class AutomaticOwnershipAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('test-post/{post}', function (Post $post) {
            return response()->json($post);
        });

        Route::get('test-posts', function () {
            return response()->json(Post::all());
        });

        Route::get('test-nested', function () {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'posts' => Post::all()
                ]
            ]);
        });
    }

    /** @test */
    public function it_automatically_attaches_ownership_to_single_model_response()
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

        $response = $this->getJson("test-post/{$post->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('ownership.owner_id', $user->id);
        $response->assertJsonPath('ownership.owner.name', 'John Doe');
    }

    /** @test */
    public function it_automatically_attaches_ownership_to_collection_response()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post1->id,
            'ownable_type' => get_class($post1),
            'is_current' => true,
        ]);

        $response = $this->getJson("test-posts");

        $response->assertStatus(200);
        $response->assertJsonPath('0.ownership.owner_id', $user->id);
        $response->assertJsonPath('1.ownership', null);
    }

    /** @test */
    public function it_automatically_attaches_ownership_to_nested_response()
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

        $response = $this->getJson("test-nested");

        $response->assertStatus(200);
        $response->assertJsonPath('data.posts.0.ownership.owner_id', $user->id);
    }

    /** @test */
    public function it_respects_disabled_configuration()
    {
        config()->set('ownable.automatic_attachment.enabled', false);

        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Sample Post', 'content' => 'Lorem ipsum']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $response = $this->getJson("test-post/{$post->id}");

        $response->assertStatus(200);
        $response->assertJsonMissingPath('ownership');
    }
}
