<?php

namespace Sowailem\Ownable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Models\OwnableModel;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class GroupedOwnershipResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('test-owner/{user}', function (User $user) {
            return response()->json($user);
        });
    }

    /** @test */
    public function it_returns_grouped_and_filtered_owned_items()
    {
        config()->set('ownable.owner_models', [User::class]);

        // 1. Setup an ownable model in the database with field filtering
        OwnableModel::create([
            'name' => 'posts',
            'model_class' => Post::class,
            'response_fields' => ['id', 'title'],
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Post Title', 'content' => 'Post Content']);

        Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => get_class($user),
            'ownable_id' => $post->id,
            'ownable_type' => get_class($post),
            'is_current' => true,
        ]);

        $user->load(['ownedItems' => function($query) {
            $query->where('is_current', true)->with('ownable');
        }]);

        $middleware = app(\Sowailem\Ownable\Http\Middleware\AttachOwnershipMiddleware::class);
        $request = \Illuminate\Http\Request::create('/test', 'GET');
        $response = new \Illuminate\Http\JsonResponse($user);

        $result = $middleware->handle($request, function() use ($response) {
            return $response;
        });

        $data = $result->getData(true);
        
        $this->assertArrayHasKey('owned_items', $data);
        
        // Check the new structure
        $this->assertCount(1, $data['owned_items']);
        $this->assertArrayHasKey('posts', $data['owned_items'][0]);
        $this->assertCount(1, $data['owned_items'][0]['posts']);
        $this->assertEquals($post->id, $data['owned_items'][0]['posts'][0]['id']);
        $this->assertEquals($post->title, $data['owned_items'][0]['posts'][0]['title']);
        $this->assertArrayNotHasKey('content', $data['owned_items'][0]['posts'][0]);
    }

    /** @test */
    public function it_groups_multiple_items_of_the_same_type()
    {
        config()->set('ownable.owner_models', [User::class]);

        OwnableModel::create([
            'name' => 'posts',
            'model_class' => Post::class,
            'response_fields' => ['title'],
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);

        Ownership::create([
            'owner_id' => $user->id, 'owner_type' => User::class,
            'ownable_id' => $post1->id, 'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        Ownership::create([
            'owner_id' => $user->id, 'owner_type' => User::class,
            'ownable_id' => $post2->id, 'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $user->load(['ownedItems' => function($query) {
            $query->where('is_current', true)->with('ownable');
        }]);

        $middleware = app(\Sowailem\Ownable\Http\Middleware\AttachOwnershipMiddleware::class);
        $response = $middleware->handle(new \Illuminate\Http\Request(), function() use ($user) {
            return new \Illuminate\Http\JsonResponse($user);
        });

        $data = $response->getData(true);
        
        $this->assertCount(1, $data['owned_items']);
        $this->assertCount(2, $data['owned_items'][0]['posts']);
        $this->assertEquals('Post 1', $data['owned_items'][0]['posts'][0]['title']);
        $this->assertEquals('Post 2', $data['owned_items'][0]['posts'][1]['title']);
    }
}
