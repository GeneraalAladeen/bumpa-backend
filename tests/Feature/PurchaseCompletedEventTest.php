<?php

namespace Tests\Feature;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseCompleted;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Order;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PurchaseCompletedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_triggers_achievement_unlock(): void
    {
        $user = User::factory()->create();
        Achievement::factory()->create(['required_purchase_count' => 1, 'cashback_percentage' => 1]);

        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $service = app(LoyaltyService::class);
        $unlocked = $service->checkAndUnlockAchievements($user);

        $this->assertCount(1, $unlocked);
        $this->assertDatabaseHas('user_achievements', ['user_id' => $user->id]);
    }

    public function test_achievement_unlock_triggers_badge_unlock(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create(['required_purchase_count' => 1]);
        Badge::factory()->create(['required_achievement_count' => 1]);

        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $service = app(LoyaltyService::class);
        $service->checkAndUnlockAchievements($user);
        $service->checkAndUnlockBadges($user);

        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id]);
    }

    public function test_full_flow_purchase_to_achievement_to_badge(): void
    {
        // In test env, queue is sync so AchievementUnlocked listeners
        // (CheckBadgesListener) run immediately inside checkAndUnlockAchievements.
        $user = User::factory()->create();

        Achievement::factory()->create(['required_purchase_count' => 1, 'cashback_percentage' => 1]);
        Achievement::factory()->create(['required_purchase_count' => 3, 'cashback_percentage' => 3]);
        Badge::factory()->create(['required_achievement_count' => 1]);
        Badge::factory()->create(['required_achievement_count' => 2]);

        $service = app(LoyaltyService::class);

        // First purchase — unlocks 1 achievement, which triggers badge check via event
        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        $service->checkAndUnlockAchievements($user);

        $this->assertCount(1, $user->fresh()->achievements);
        $this->assertCount(1, $user->fresh()->badges);

        // Two more purchases — unlocks second achievement and second badge
        Order::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'completed']);
        $service->checkAndUnlockAchievements($user->fresh());

        $this->assertCount(2, $user->fresh()->achievements);
        $this->assertCount(2, $user->fresh()->badges);
    }

    public function test_purchase_completed_event_is_dispatched(): void
    {
        Event::fake([PurchaseCompleted::class]);

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/purchases', [
            'total_amount' => 500,
        ]);

        Event::assertDispatched(PurchaseCompleted::class);
    }
}
