<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
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
            'role' => 'admin',
            'email' => 'test@example.com',
        ]);

        Category::factory(3)
            ->has(Post::factory(4))
            ->create();
    }
}
