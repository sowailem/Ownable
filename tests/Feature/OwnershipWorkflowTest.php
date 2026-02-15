<?php

namespace Sowailem\Ownable\Tests\Feature;

use Sowailem\Ownable\Facades\Owner;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;

class OwnershipWorkflowTest extends TestCase
{
    /** @test */
    public function it_can_handle_complete_ownership_lifecycle()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Step 1: Initial ownership assignment
        Owner::give($user1, $post);
        
        $this->assertTrue(Owner::check($user1, $post));
        $this->assertEquals($user1->id, Owner::currentOwner($post)->id);
        $this->assertEquals(1, Ownership::count());

        // Step 2: Transfer ownership
        Owner::transfer($user1, $user2, $post);
        
        $this->assertFalse(Owner::check($user1, $post));
        $this->assertTrue(Owner::check($user2, $post));
        $this->assertEquals($user2->id, Owner::currentOwner($post)->id);
        $this->assertEquals(2, Ownership::count());

        // Step 3: Another transfer
        Owner::transfer($user2, $user3, $post);
        
        $this->assertFalse(Owner::check($user2, $post));
        $this->assertTrue(Owner::check($user3, $post));
        $this->assertEquals($user3->id, Owner::currentOwner($post)->id);
        $this->assertEquals(3, Ownership::count());

        // Step 4: Remove ownership
        Owner::remove($post);
        
        $this->assertFalse(Owner::check($user1, $post));
        $this->assertFalse(Owner::check($user2, $post));
        $this->assertFalse(Owner::check($user3, $post));
        $this->assertNull(Owner::currentOwner($post));
        $this->assertEquals(3, Ownership::count()); // History remains, none current

        // Verify ownership history is maintained
        $ownerships = Ownership::orderBy('created_at')->get();
        $this->assertEquals($user1->id, $ownerships[0]->owner_id);
        $this->assertFalse($ownerships[0]->is_current);
        $this->assertEquals($user2->id, $ownerships[1]->owner_id);
        $this->assertFalse($ownerships[1]->is_current);
    }

    /** @test */
    public function it_can_handle_multiple_objects_ownership()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);
        $post3 = Post::create(['title' => 'Post 3', 'content' => 'Content 3']);

        // User owns multiple posts
        Owner::give($user, $post1);
        Owner::give($user, $post2);
        Owner::give($user, $post3);

        $this->assertTrue(Owner::check($user, $post1));
        $this->assertTrue(Owner::check($user, $post2));
        $this->assertTrue(Owner::check($user, $post3));
        
        $this->assertEquals(3, Ownership::where('owner_id', $user->id)->where('is_current', true)->count());

        // Transfer one post to another user
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        Owner::transfer($user, $user2, $post2);

        $this->assertTrue(Owner::check($user, $post1));
        $this->assertFalse(Owner::check($user, $post2));
        $this->assertTrue(Owner::check($user, $post3));
        $this->assertTrue(Owner::check($user2, $post2));
        
        $this->assertEquals(2, Ownership::where('owner_id', $user->id)->where('is_current', true)->count());
        $this->assertEquals(1, Ownership::where('owner_id', $user2->id)->where('is_current', true)->count());
    }

    /** @test */
    public function it_can_handle_multiple_users_owning_different_objects()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);
        $post3 = Post::create(['title' => 'Post 3', 'content' => 'Content 3']);

        // Distribute ownership
        Owner::give($user1, $post1);
        Owner::give($user2, $post2);
        Owner::give($user3, $post3);

        // Verify each user owns their respective post
        $this->assertTrue(Owner::check($user1, $post1));
        $this->assertTrue(Owner::check($user2, $post2));
        $this->assertTrue(Owner::check($user3, $post3));

        // Verify cross-ownership checks
        $this->assertFalse(Owner::check($user1, $post2));
        $this->assertFalse(Owner::check($user1, $post3));
        $this->assertFalse(Owner::check($user2, $post1));
        $this->assertFalse(Owner::check($user2, $post3));
        $this->assertFalse(Owner::check($user3, $post1));
        $this->assertFalse(Owner::check($user3, $post2));

        // Complex transfer scenario
        Owner::transfer($user1, $user2, $post1); // user2 now owns post1 and post2
        Owner::transfer($user2, $user3, $post2); // user3 now owns post2 and post3

        $this->assertFalse(Owner::check($user1, $post1));
        $this->assertTrue(Owner::check($user2, $post1));
        $this->assertFalse(Owner::check($user2, $post2));
        $this->assertTrue(Owner::check($user3, $post2));
        $this->assertTrue(Owner::check($user3, $post3));
    }

    /** @test */
    public function it_maintains_data_integrity_during_complex_operations()
    {
        $users = collect();
        $posts = collect();

        // Create test data
        for ($i = 1; $i <= 5; $i++) {
            $users->push(User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]));
            
            $posts->push(Post::create([
                'title' => "Post {$i}",
                'content' => "Content {$i}"
            ]));
        }

        // Complex ownership operations
        foreach ($posts as $index => $post) {
            $owner = $users->get($index);
            Owner::give($owner, $post);
        }

        // Verify initial state
        $this->assertEquals(5, Ownership::where('is_current', true)->count());
        $this->assertEquals(5, Ownership::count());

        // Perform multiple transfers
        Owner::transfer($users[0], $users[1], $posts[0]); // User 1 gets post 0
        Owner::transfer($users[1], $users[2], $posts[1]); // User 2 gets post 1
        Owner::transfer($users[2], $users[3], $posts[2]); // User 3 gets post 2

        // Verify data integrity
        $this->assertEquals(5, Ownership::where('is_current', true)->count());
        $this->assertEquals(8, Ownership::count()); // 5 initial + 3 transfers

        // Verify current ownership
        $this->assertFalse(Owner::check($users[0], $posts[0]));
        $this->assertTrue(Owner::check($users[1], $posts[0]));
        
        $this->assertFalse(Owner::check($users[1], $posts[1]));
        $this->assertTrue(Owner::check($users[2], $posts[1]));
        
        $this->assertFalse(Owner::check($users[2], $posts[2]));
        $this->assertTrue(Owner::check($users[3], $posts[2]));
        
        $this->assertTrue(Owner::check($users[3], $posts[3]));
        $this->assertTrue(Owner::check($users[4], $posts[4])); // Unchanged
    }

    /** @test */
    public function it_handles_ownership_when_models_are_deleted()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        Owner::give($user, $post);
        $this->assertEquals(1, Ownership::count());

        // Delete the ownable model
        $post->delete();

        // Since we are isolated, we don't automatically delete ownership records.
        $this->assertEquals(1, Ownership::count());
    }

    /** @test */
    public function it_can_handle_ownership_reassignment_scenarios()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Initial ownership
        Owner::give($user1, $post);
        
        // Try to give ownership to same user (should not create duplicate or return error)
        Owner::give($user1, $post);
        $this->assertEquals(1, Ownership::where('is_current', true)->count());

        // Reassign to different user multiple times
        Owner::give($user2, $post);
        Owner::give($user1, $post);
        Owner::give($user2, $post);

        // Should have ownership history but only one current
        $this->assertEquals(1, Ownership::where('is_current', true)->count());
        $this->assertTrue(Ownership::count() > 1);
        $this->assertTrue(Owner::check($user2, $post));
        $this->assertFalse(Owner::check($user1, $post));
    }

    /** @test */
    public function it_provides_accurate_ownership_queries()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);

        // Create ownership history
        Owner::give($user1, $post1);
        Owner::give($user1, $post2);
        Owner::transfer($user1, $user2, $post1);

        // Test relationship queries via Ownership model
        $user1CurrentOwnablesCount = Ownership::where('owner_id', $user1->id)
            ->where('owner_type', get_class($user1))
            ->where('is_current', true)
            ->count();
        
        $user2CurrentOwnablesCount = Ownership::where('owner_id', $user2->id)
            ->where('owner_type', get_class($user2))
            ->where('is_current', true)
            ->count();

        $this->assertEquals(1, $user1CurrentOwnablesCount); // Only post2
        $this->assertEquals(1, $user2CurrentOwnablesCount); // Only post1

        // Test post owners
        $post1OwnersCount = Ownership::where('ownable_id', $post1->id)
            ->where('ownable_type', get_class($post1))
            ->count();
            
        $post1CurrentOwner = Ownership::where('ownable_id', $post1->id)
            ->where('ownable_type', get_class($post1))
            ->where('is_current', true)
            ->first()
            ->owner;

        $this->assertEquals(2, $post1OwnersCount); // Both users in history
        $this->assertEquals($user2->id, $post1CurrentOwner->id); // Current owner is user2
    }
}