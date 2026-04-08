<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
