<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NestedCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $this->expectException(ValidationException::class);

        $category->update([
            'parent_id' => $category->id,
        ]);
    }

    public function test_category_cannot_be_nested_inside_its_own_child(): void
    {
        $parent = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);
        $child = Category::factory()->create([
            'parent_id' => $parent->id,
            'visibility' => Visibility::Public,
        ]);

        $this->expectException(ValidationException::class);

        $parent->update([
            'parent_id' => $child->id,
        ]);
    }

    public function test_public_category_can_be_nested_inside_private_ancestor(): void
    {
        $parent = Category::factory()->create([
            'visibility' => Visibility::Private,
        ]);

        $category = Category::factory()->create([
            'parent_id' => $parent->id,
            'visibility' => Visibility::Public,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_select_label_includes_parent_categories(): void
    {
        $parent = Category::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);

        $child = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
            'visibility' => Visibility::Private,
        ]);

        $this->assertSame('Parent / Child (Private)', $child->getSelectLabel());
    }

    public function test_category_slug_must_be_unique_within_parent(): void
    {
        $parent = Category::factory()->create();

        Category::factory()->for($parent, 'parent')->create(['slug' => 'child']);

        $this->expectException(ValidationException::class);

        Category::factory()->for($parent, 'parent')->create(['slug' => 'child']);
    }

    public function test_root_category_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'category']);

        $this->expectException(ValidationException::class);

        Category::factory()->create(['slug' => 'category']);
    }

    public function test_factories_populate_slugs_even_when_events_are_muted(): void
    {
        $category = Category::factory()->createQuietly();
        $post = Post::factory()->for($category)->createQuietly();

        $this->assertSame(Str::slug($category->name), $category->fresh()->slug);
        $this->assertSame(Str::slug($post->title), $post->fresh()->slug);
    }

    public function test_categories_can_share_a_slug_under_different_parents(): void
    {
        $firstParent = Category::factory()->create(['name' => 'First Parent', 'slug' => 'first-parent', 'visibility' => Visibility::Public]);
        $secondParent = Category::factory()->create(['name' => 'Second Parent', 'slug' => 'second-parent', 'visibility' => Visibility::Public]);

        $firstChild = Category::factory()->for($firstParent, 'parent')->create(['name' => 'Child', 'slug' => 'child', 'visibility' => Visibility::Public]);
        $secondChild = Category::factory()->for($secondParent, 'parent')->create(['name' => 'Child', 'slug' => 'child', 'visibility' => Visibility::Public]);

        $this->assertSame('first-parent/child', $firstChild->getSlugPath());
        $this->assertSame('second-parent/child', $secondChild->getSlugPath());
        $this->assertTrue(Category::findBySlugPath('first-parent/child')->is($firstChild));
        $this->assertTrue(Category::findBySlugPath('second-parent/child')->is($secondChild));
    }

    public function test_category_view_uses_the_slug_path(): void
    {
        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
        ]);

        $this->assertSame(url('/categories/view/category'), CategoryResource::getUrl('view', ['record' => $category]));
    }

    public function test_category_edit_uses_the_record_id(): void
    {
        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
        ]);

        $this->assertSame(url('/categories/' . $category->id . '/edit'), CategoryResource::getUrl('edit', ['record' => $category]));
    }

    public function test_post_slug_must_be_unique(): void
    {
        $category = Category::factory()->create();

        Post::factory()->for($category)->create([
            'title' => 'Post',
            'slug' => 'post',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Post::factory()->for($category)->create([
            'title' => 'Post',
            'slug' => 'post',
        ]);
    }

    public function test_post_view_url_falls_back_to_the_post_category(): void
    {
        $category = Category::factory()->create(['name' => 'Category', 'slug' => 'category', 'visibility' => Visibility::Public]);
        $post = Post::factory()->for($category)->create(['title' => 'Post', 'slug' => 'post', 'visibility' => Visibility::Public]);

        $this->assertSame(
            url('/posts/view/category/post'),
            PostResource::getUrl('view', ['record' => $post]),
        );
    }

    public function test_post_edit_uses_the_record_id(): void
    {
        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
        ]);

        $post = Post::factory()->for($category)->create([
            'title' => 'Post',
            'slug' => 'post',
        ]);

        $this->assertSame(url('/posts/' . $post->id . '/edit'), PostResource::getUrl('edit', ['record' => $post]));
    }

    public function test_post_view_uses_nested_category_path(): void
    {
        $parent = Category::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);

        $category = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $post = Post::factory()->for($category)->create([
            'title' => 'Post',
            'slug' => 'post',
        ]);

        $this->assertSame(url('/posts/view/parent/child/post'), PostResource::getUrl('view', ['record' => $post]));
    }

    public function test_null_slugs_fall_back_to_slugified_names_in_urls(): void
    {
        $category = Category::factory()->createQuietly([
            'name' => 'Parent Category',
            'slug' => null,
        ]);

        $post = Post::factory()->for($category)->createQuietly([
            'title' => 'Post',
            'slug' => null,
        ]);

        $this->assertSame(url('/categories/view/parent-category'), CategoryResource::getUrl('view', ['record' => $category]));
        $this->assertSame(url('/posts/view/parent-category/post'), PostResource::getUrl('view', ['record' => $post]));
        $this->assertTrue(Category::findBySlugPath('parent-category', Category::withoutGlobalScopes())->is($category));
    }

    public function test_posts_can_share_a_slug_in_different_categories(): void
    {
        $firstCategory = Category::factory()->create([
            'slug' => 'first-category',
        ]);

        $secondCategory = Category::factory()->create([
            'slug' => 'second-category',
        ]);

        Post::factory()->for($firstCategory)->create([
            'slug' => 'install',
        ]);

        Post::factory()->for($secondCategory)->create([
            'slug' => 'install',
        ]);

        $this->assertDatabaseCount('posts', 2);
    }

    public function test_nested_categories_use_the_full_path_in_urls(): void
    {
        $knowledgeBase = Category::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);

        $rootChild = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
        ]);

        $nestedChild = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $knowledgeBase->id,
        ]);

        $this->assertSame(url('/categories/view/child'), CategoryResource::getUrl('view', ['record' => $rootChild]));
        $this->assertSame(url('/categories/view/parent/child'), CategoryResource::getUrl('view', ['record' => $nestedChild]));
    }

    public function test_sidebar_opens_the_active_nested_branch_for_posts(): void
    {
        $user = User::factory()->create();

        $parent = Category::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
        ]);

        $child = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        Category::factory()->create([
            'name' => 'Grandchild',
            'slug' => 'grandchild',
            'parent_id' => $child->id,
        ]);

        $selectedPost = Post::factory()->for($child)->create([
            'title' => 'First Post',
            'slug' => 'first-post',
        ]);

        Post::factory()->for($child)->create([
            'title' => 'Second Post',
            'slug' => 'second-post',
        ]);

        Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
        ]);

        $this->actingAs($user)
            ->get(PostResource::getUrl('view', ['record' => $selectedPost]))
            ->assertOk()
            ->assertSee('Grandchild')
            ->assertSee('Second Post');
    }
}
