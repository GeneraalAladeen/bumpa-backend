<?php

namespace App\Events;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeUnlocked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Badge $badge,
    ) {
    }
}
