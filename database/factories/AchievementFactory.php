<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'required_purchase_count' => fake()->numberBetween(1, 50),
            'cashback_percentage' => fake()->numberBetween(1, 10),
        ];
    }
}
