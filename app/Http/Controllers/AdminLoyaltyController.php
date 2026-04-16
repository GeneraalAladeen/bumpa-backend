<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminLoyaltyController extends Controller
{
    /**
     * GET /api/admin/users/achievements
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::with('badges')
            ->withCount(['achievements', 'orders'])
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->simplePaginate($request->input('per_page', 15));

        return UserResource::collection($users);
    }
}
