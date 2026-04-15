<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentBadge = $this->badges->sortByDesc('required_achievement_count')->first();

        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'total_purchases' => $this->orders_count ?? 0,
            'achievements_count' => $this->achievements_count ?? 0,
            'current_badge' => $currentBadge?->name,
        ];
    }
}
