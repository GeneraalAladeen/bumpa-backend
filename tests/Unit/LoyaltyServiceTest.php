<?php

namespace Tests\Unit;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Order;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoyaltyService();
    }

    public function test_unlocks_achievement_when_purchase_count_met(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create(['required_purchase_count' => 1]);

        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $unlocked = $this->service->checkAndUnlockAchievements($user);

        $this->assertCount(1, $unlocked);
        $this->assertEquals($achievement->id, $unlocked->first()->id);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_does_not_unlock_achievement_when_count_not_met(): void
    {
        $user = User::factory()->create();
        Achievement::factory()->create(['required_purchase_count' => 5]);

        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $unlocked = $this->service->checkAndUnlockAchievements($user);

        $this->assertCount(0, $unlocked);
    }

    public function test_does_not_double_unlock_achievement(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create(['required_purchase_count' => 1]);
        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $this->service->checkAndUnlockAchievements($user);
        $unlocked = $this->service->checkAndUnlockAchievements($user);

        $this->assertCount(0, $unlocked);
        $this->assertCount(1, $user->achievements);
    }

    public function test_unlocks_badge_when_achievement_count_met(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create(['required_achievement_count' => 1]);
        $achievement = Achievement::factory()->create(['required_purchase_count' => 1]);

        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $unlocked = $this->service->checkAndUnlockBadges($user);

        $this->assertCount(1, $unlocked);
        $this->assertEquals($badge->id, $unlocked->first()->id);
    }

    public function test_does_not_unlock_badge_when_count_not_met(): void
    {
        $user = User::factory()->create();
        Badge::factory()->create(['required_achievement_count' => 3]);

        $unlocked = $this->service->checkAndUnlockBadges($user);

        $this->assertCount(0, $unlocked);
    }

    public function test_calculates_cashback_percentage_from_highest_achievement(): void
    {
        $user = User::factory()->create();
        $a1 = Achievement::factory()->create(['cashback_percentage' => 2]);
        $a2 = Achievement::factory()->create(['cashback_percentage' => 5]);

        $user->achievements()->attach($a1->id, ['unlocked_at' => now()]);
        $user->achievements()->attach($a2->id, ['unlocked_at' => now()]);

        $percentage = $this->service->calculateCashbackPercentage($user);

        $this->assertEquals(5, $percentage);
    }

    public function test_cashback_percentage_zero_when_no_achievements(): void
    {
        $user = User::factory()->create();

        $percentage = $this->service->calculateCashbackPercentage($user);

        $this->assertEquals(0, $percentage);
    }

    public function test_get_user_progress_returns_complete_data(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'required_purchase_count' => 1,
            'cashback_percentage' => 2,
        ]);
        Badge::factory()->create(['required_achievement_count' => 1]);

        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $progress = $this->service->getUserProgress($user);

        $this->assertEquals($user->id, $progress['user_id']);
        $this->assertEquals(1, $progress['total_purchases']);
        $this->assertCount(1, $progress['unlocked_achievements']);
        $this->assertArrayHasKey('all_achievements', $progress);
        $this->assertArrayHasKey('cashback', $progress);
        $this->assertEquals(2, $progress['cashback']['current_percentage']);
    }

    public function test_pending_orders_not_counted(): void
    {
        $user = User::factory()->create();
        Achievement::factory()->create(['required_purchase_count' => 1]);

        Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $unlocked = $this->service->checkAndUnlockAchievements($user);

        $this->assertCount(0, $unlocked);
    }
}
