<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_register_when_no_users_exist(): void
    {
        $this->get('/')
            ->assertRedirect(route('filament.admin.auth.register'));
    }

    public function test_register_redirects_to_login_when_users_exist(): void
    {
        User::factory()->create();

        $this->get(route('filament.admin.auth.register'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_login_is_available_when_users_exist(): void
    {
        User::factory()->create();

        $this->get(route('filament.admin.auth.login'))
            ->assertOk();
    }

    public function test_guests_are_redirected_to_login_from_edit_pages(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($category)->create([
            'visibility' => Visibility::Public,
        ]);

        $this->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_authenticated_users_can_open_edit_pages(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($category)->create([
            'visibility' => Visibility::Public,
        ]);

        $this->actingAs($user)
            ->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertOk();
    }

    public function test_guests_are_redirected_to_login_from_private_posts(): void
    {
        User::factory()->create();

        $category = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $post = Post::factory()->for($category)->create([
            'visibility' => Visibility::Private,
        ]);

        $this->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_missing_post_pages_redirect_home(): void
    {
        User::factory()->create();

        $this->get('/posts/view/missing-category/missing-post')
            ->assertRedirect('/');
    }
}
