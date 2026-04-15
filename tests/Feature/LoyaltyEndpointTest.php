<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_user_achievements_returns_progress(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'required_purchase_count' => 1,
            'cashback_percentage' => 2,
        ]);
        Badge::factory()->create(['required_achievement_count' => 1]);

        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->id}/achievements");

        $response->assertOk()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.total_purchases', 1)
            ->assertJsonCount(1, 'data.unlocked_achievements');
    }

    public function test_get_user_achievements_requires_auth(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(401);
    }

    public function test_purchase_creates_order_and_returns_201(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/purchases', [
                'total_amount' => 1500.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.user_id', $user->id);

        $this->assertEquals(1500.00, $response->json('order.total_amount'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    public function test_purchase_validates_total_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/purchases', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_amount']);
    }

    public function test_purchase_requires_auth(): void
    {
        $response = $this->postJson('/api/purchases', [
            'total_amount' => 100,
        ]);

        $response->assertStatus(401);
    }
}
