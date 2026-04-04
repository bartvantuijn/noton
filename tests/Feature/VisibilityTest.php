<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_public_posts(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($category)->create([
            'title' => 'Public post',
            'visibility' => Visibility::Public,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertOk()
            ->assertSee('Public post');
    }

    public function test_guests_cannot_view_private_posts(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($category)->create([
            'visibility' => Visibility::Private,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertNotFound();
    }

    public function test_guests_cannot_view_posts_from_private_categories(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $post = Post::factory()->for($category)->create([
            'visibility' => Visibility::Public,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertNotFound();
    }

    public function test_guests_cannot_view_private_categories(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $this->get(CategoryResource::getUrl('view', ['record' => $category]))
            ->assertNotFound();
    }
}
