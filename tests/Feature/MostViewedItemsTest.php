<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MostViewedItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_most_viewed_shows_posts_and_categories(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create([
            'name' => 'Hosting category',
            'content' => 'Category preview text for most viewed.',
            'views' => 20,
            'visibility' => Visibility::Public,
        ]);

        Post::factory()->for($category)->create([
            'title' => 'Popular post',
            'content' => 'Post preview text for most viewed.',
            'views' => 10,
            'visibility' => Visibility::Public,
        ]);

        $category->attachTag(Tag::factory()->create(['name' => 'Servers']));

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSeeText('Most viewed')
            ->assertSeeText('Hosting category')
            ->assertSeeText('Servers')
            ->assertSeeText('Category preview text for most viewed.')
            ->assertSeeText('Popular post')
            ->assertSeeText('Post preview text for most viewed.');
    }
}
