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
            'title' => 'Public Post',
            'visibility' => Visibility::Public,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertOk()
            ->assertSee('Public Post');
    }

    public function test_guests_are_redirected_from_private_posts(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($category)->create([
            'visibility' => Visibility::Private,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertRedirect(route('filament.admin.auth.login'));
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
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_guests_are_redirected_from_private_categories(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $this->get(CategoryResource::getUrl('view', ['record' => $category]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_guests_are_redirected_from_public_categories_inside_private_categories(): void
    {
        User::factory()->create();

        $parent = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
            'visibility' => Visibility::Public,
        ]);

        $this->get(CategoryResource::getUrl('view', ['record' => $child]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_guests_cannot_view_public_posts_inside_private_category_branches(): void
    {
        User::factory()->create();

        $parent = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($child)->create([
            'visibility' => Visibility::Public,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_guests_do_not_see_private_posts_on_category_pages(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        Post::factory()->for($category)->create([
            'title' => 'Private Post',
            'visibility' => Visibility::Private,
        ]);

        $this->get(CategoryResource::getUrl('view', ['record' => $category]))
            ->assertOk()
            ->assertDontSee('Private Post');
    }

    public function test_guest_queries_hide_public_items_inside_private_category_branches(): void
    {
        User::factory()->create();

        $parent = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($child)->create([
            'visibility' => Visibility::Public,
        ]);

        $this->assertFalse(Category::pluck('id')->contains($child->id));
        $this->assertFalse(Post::pluck('id')->contains($post->id));
    }
}
