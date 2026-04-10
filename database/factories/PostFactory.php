<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
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
                '```php
<?php
    echo "Hello Noton!";
?>
```',
                fake()->paragraph(),
                '- ' . ucfirst(fake()->words(3, true)),
                '- ' . ucfirst(fake()->words(3, true)),
                '- ' . ucfirst(fake()->words(3, true)),
            ]),
            'visibility' => fake()->randomElement(Visibility::values()),
            'created_at' => $created = fake()->dateTimeBetween('-1 years'),
            'updated_at' => fake()->dateTimeBetween($created),
        ];
    }
}
