<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'total_purchases' => $this->whenCounted('orders'),
            'achievements_count' => $this->whenCounted('achievements'),
            'current_badge' => $this->whenLoaded('badges', function () {
                return $this->badges->sortByDesc('required_achievement_count')->first()?->name;
            }),
            'created_at' => $this->created_at,
        ];
    }
}
