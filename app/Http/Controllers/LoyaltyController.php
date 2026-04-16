<?php

namespace App\Http\Controllers;

use App\Events\PurchaseCompleted;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LoyaltyController extends Controller
{
    public function __construct(private LoyaltyService $loyaltyService)
    {
    }

    /**
     * GET /api/users/{user}/achievements
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $this->loyaltyService->getUserProgress($user),
        ]);
    }

    /**
     * POST /api/purchases — simulate a purchase and trigger loyalty events.
     */
    public function purchase(StorePurchaseRequest $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::create([
            'user_id' => $user->id,
            'order_reference' => 'ORD-' . Str::random(10),
            'total_amount' => $request->total_amount,
            'status' => 'completed',
        ]);

        PurchaseCompleted::dispatch($order);

        return response()->json([
            'message' => 'Purchase recorded successfully.',
            'order' => new OrderResource($order),
        ], 201);
    }
}
