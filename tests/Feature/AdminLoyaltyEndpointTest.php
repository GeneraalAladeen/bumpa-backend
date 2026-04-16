<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoyaltyEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_all_users_achievements(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create(['required_purchase_count' => 1]);
        $badge = Badge::factory()->create(['required_achievement_count' => 1]);

        Order::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'completed']);
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);
        $user->badges()->attach($badge->id, ['unlocked_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users/achievements');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'is_admin', 'total_purchases', 'achievements_count', 'current_badge', 'created_at'],
                ],
            ]);
    }

    public function test_non_admin_cannot_access_admin_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/users/achievements');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin_endpoint(): void
    {
        $response = $this->getJson('/api/admin/users/achievements');

        $response->assertStatus(401);
    }

    public function test_admin_endpoint_supports_search(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users/achievements?search=John');

        $response->assertOk();
        $this->assertTrue(
            collect($response->json('data'))->contains('name', 'John Doe')
        );
    }

    public function test_admin_endpoint_supports_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(20)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users/achievements?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', fn ($v) => $v == 5);
    }
}
