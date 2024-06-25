<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_post()
    {
        $response = $this->postJson('/api/posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'title' => 'Test Post',
                     'body' => 'This is a test post.',
                 ]);
    }

    public function test_can_get_all_posts()
    {
        Post::factory()->count(3)->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_can_get_single_post()
    {
        $post = Post::factory()->create();

        $response = $this->getJson('/api/posts/' . $post->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'title' => $post->title,
                     'body' => $post->body,
                 ]);
    }

    public function test_can_update_post()
    {
        $post = Post::factory()->create();

        $response = $this->putJson('/api/posts/' . $post->id, [
            'title' => 'Updated Title',
            'body' => 'Updated Body',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'title' => 'Updated Title',
                     'body' => 'Updated Body',
                 ]);
    }

    public function test_can_delete_post()
    {
        $post = Post::factory()->create();

        $response = $this->deleteJson('/api/posts/' . $post->id);

        $response->assertStatus(204);
    }
}
