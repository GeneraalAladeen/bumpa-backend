<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use App\Services\LoyaltyService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckAchievementsListener implements ShouldQueue
{
    public function __construct(private LoyaltyService $loyaltyService)
    {
    }

    public function handle(PurchaseCompleted $event): void
    {
        $user = $event->order->user;

        if (! $user) {
            return;
        }

        $this->loyaltyService->checkAndUnlockAchievements($user);
    }
}
