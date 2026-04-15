<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Services\LoyaltyService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckBadgesListener implements ShouldQueue
{
    public function __construct(private LoyaltyService $loyaltyService)
    {
    }

    public function handle(AchievementUnlocked $event): void
    {
        $this->loyaltyService->checkAndUnlockBadges($event->user);
    }
}
