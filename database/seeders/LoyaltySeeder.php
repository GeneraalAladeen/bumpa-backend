<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Badge;
use Illuminate\Database\Seeder;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['name' => 'First Purchase', 'slug' => 'first-purchase', 'required_purchase_count' => 1, 'cashback_percentage' => 1],
            ['name' => '5 Purchases', 'slug' => '5-purchases', 'required_purchase_count' => 5, 'cashback_percentage' => 2],
            ['name' => '10 Purchases', 'slug' => '10-purchases', 'required_purchase_count' => 10, 'cashback_percentage' => 3],
            ['name' => '25 Purchases', 'slug' => '25-purchases', 'required_purchase_count' => 25, 'cashback_percentage' => 5],
            ['name' => '50 Purchases', 'slug' => '50-purchases', 'required_purchase_count' => 50, 'cashback_percentage' => 7],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['slug' => $achievement['slug']], $achievement);
        }

        $badges = [
            ['name' => 'Beginner', 'slug' => 'beginner', 'required_achievement_count' => 1],
            ['name' => 'Intermediate', 'slug' => 'intermediate', 'required_achievement_count' => 2],
            ['name' => 'Advanced', 'slug' => 'advanced', 'required_achievement_count' => 3],
            ['name' => 'Master', 'slug' => 'master', 'required_achievement_count' => 5],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
