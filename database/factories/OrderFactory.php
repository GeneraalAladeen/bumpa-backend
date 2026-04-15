<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_reference' => 'ORD-' . fake()->unique()->numerify('######'),
            'total_amount' => fake()->randomFloat(2, 10, 5000),
            'status' => 'completed',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
