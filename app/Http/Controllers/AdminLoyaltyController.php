<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminUserResource;
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
            ->paginate($request->input('per_page', 15));

        return AdminUserResource::collection($users);
    }
}
