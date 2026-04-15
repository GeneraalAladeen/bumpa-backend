<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Collection;

class LoyaltyService
{
    /**
     * Check and unlock any new achievements for the user based on purchase count.
     */
    public function checkAndUnlockAchievements(User $user): Collection
    {
        $purchaseCount = $user->orders()->where('status', 'completed')->count();
        $unlockedIds = $user->achievements()->pluck('achievements.id');

        $newAchievements = Achievement::where('required_purchase_count', '<=', $purchaseCount)
            ->whereNotIn('id', $unlockedIds)
            ->orderBy('required_purchase_count')
            ->get();

        foreach ($newAchievements as $achievement) {
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);

            AchievementUnlocked::dispatch($user, $achievement);
        }

        return $newAchievements;
    }

    /**
     * Check and unlock any new badges for the user based on achievement count.
     */
    public function checkAndUnlockBadges(User $user): Collection
    {
        $achievementCount = $user->achievements()->count();
        $unlockedBadgeIds = $user->badges()->pluck('badges.id');

        $newBadges = Badge::where('required_achievement_count', '<=', $achievementCount)
            ->whereNotIn('id', $unlockedBadgeIds)
            ->orderBy('required_achievement_count')
            ->get();

        foreach ($newBadges as $badge) {
            $user->badges()->attach($badge->id, [
                'unlocked_at' => now(),
            ]);

            BadgeUnlocked::dispatch($user, $badge);
        }

        return $newBadges;
    }

    /**
     * Get the cashback percentage based on the user's highest unlocked achievement.
     */
    public function calculateCashbackPercentage(User $user): int
    {
        return $user->achievements()
            ->orderByDesc('cashback_percentage')
            ->value('cashback_percentage') ?? 0;
    }

    /**
     * Assemble full loyalty progress data for the API response.
     */
    public function getUserProgress(User $user): array
    {
        $user->loadCount(['orders' => fn ($q) => $q->where('status', 'completed')]);

        $unlockedAchievements = $user->achievements()
            ->orderBy('required_purchase_count')
            ->get();

        $allAchievements = Achievement::orderBy('required_purchase_count')->get();

        $nextAchievement = $allAchievements
            ->whereNotIn('id', $unlockedAchievements->pluck('id'))
            ->first();

        $currentBadge = $user->badges()
            ->orderByDesc('required_achievement_count')
            ->first();

        $allBadges = Badge::orderBy('required_achievement_count')->get();

        $nextBadge = $allBadges
            ->whereNotIn('id', $user->badges()->pluck('badges.id'))
            ->first();

        $recentTransactions = $user->cashbackTransactions()
            ->with('order')
            ->latest()
            ->take(10)
            ->get();

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'total_purchases' => $user->orders_count,
            'unlocked_achievements' => $unlockedAchievements->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'slug' => $a->slug,
                'required_purchase_count' => $a->required_purchase_count,
                'cashback_percentage' => $a->cashback_percentage,
                'unlocked_at' => $a->pivot->unlocked_at,
            ]),
            'all_achievements' => $allAchievements->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'slug' => $a->slug,
                'required_purchase_count' => $a->required_purchase_count,
                'cashback_percentage' => $a->cashback_percentage,
                'unlocked' => $unlockedAchievements->contains('id', $a->id),
            ]),
            'next_achievement' => $nextAchievement ? [
                'name' => $nextAchievement->name,
                'purchases_remaining' => $nextAchievement->required_purchase_count - $user->orders_count,
            ] : null,
            'current_badge' => $currentBadge ? [
                'id' => $currentBadge->id,
                'name' => $currentBadge->name,
                'slug' => $currentBadge->slug,
                'unlocked_at' => $currentBadge->pivot->unlocked_at,
            ] : null,
            'next_badge' => $nextBadge ? [
                'name' => $nextBadge->name,
                'achievements_remaining' => $nextBadge->required_achievement_count - $unlockedAchievements->count(),
            ] : null,
            'cashback' => [
                'current_percentage' => $this->calculateCashbackPercentage($user),
                'total_earned' => $user->cashbackTransactions()->where('status', 'success')->sum('amount'),
                'recent_transactions' => $recentTransactions->map(fn ($t) => [
                    'id' => $t->id,
                    'amount' => $t->amount,
                    'order_reference' => $t->order->order_reference,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                ]),
            ],
        ];
    }
}
