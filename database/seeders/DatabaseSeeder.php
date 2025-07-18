<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $tags = Tag::factory(10)->create();

        Category::factory(3)
            ->has(Post::factory(4)
                ->afterCreating(function (Post $post) use ($tags) {
                    $post->attachTags($tags->random(3));
                })
            )
            ->createQuietly();
    }
}
