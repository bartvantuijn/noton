<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
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

        $settings = Setting::singleton();

        $settings->set('appearance', [
            'color' => fake()->hexColor,
        ]);

        $settings->set('notice', [
            'enabled' => true,
            'title' => ucwords(fake()->words(2, true)),
            'style' => fake()->randomElement(['primary', 'success', 'warning', 'danger']),
            'message' => fake()->paragraph(),
        ]);

        $tags = Tag::factory(10)->create();

        Category::factory(3)
            ->has(
                Post::factory(4)
                    ->afterCreating(function (Post $post) use ($tags) {
                        $post->attachTags($tags->random(3));
                    })
            )
            ->has(
                Category::factory(3)
                    ->has(
                        Post::factory(4)
                            ->afterCreating(function (Post $post) use ($tags) {
                                $post->attachTags($tags->random(3));
                            })
                    ),
                'children'
            )
            ->createQuietly();
    }
}
