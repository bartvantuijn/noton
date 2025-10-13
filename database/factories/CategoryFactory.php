<?php

namespace Database\Factories;

use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->words(2, true)),
            'visibility' => fake()->randomElement(Visibility::values()),
            'created_at' => $created = fake()->dateTimeBetween('-1 years'),
            'updated_at' => fake()->dateTimeBetween($created),
        ];
    }
}
