<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->words(3, true)),
            'content' => implode("\n\n", [
                '# ' . ucfirst(fake()->words(3, true)),
                fake()->paragraph(),
                '> ' . fake()->sentence(),
                '## ' . ucfirst(fake()->words(3, true)),
                '```php',
                'echo "Hello world!";',
                '```',
                fake()->paragraph(),
                '- ' . ucfirst(fake()->words(3, true)),
                '- ' . ucfirst(fake()->words(3, true)),
                '- ' . ucfirst(fake()->words(3, true)),
            ]),
        ];
    }
}
