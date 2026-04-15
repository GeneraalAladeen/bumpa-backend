<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockPaystackGateway implements PaymentGatewayInterface
{
    public function disburseCashback(User $user, float $amount, string $reference): CashbackResult
    {
        $successRate = config('services.payment_gateway.success_rate', 80);
        $isSuccess = rand(1, 100) <= $successRate;

        Log::info('MockPaystackGateway: disburseCashback', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reference' => $reference,
            'success' => $isSuccess,
        ]);

        if ($isSuccess) {
            return new CashbackResult(
                success: true,
                providerReference: 'PSK_' . Str::random(20),
            );
        }

        $failureReasons = [
            'Insufficient funds in disbursement account',
            'Transfer service temporarily unavailable',
            'Recipient account validation failed',
            'Transaction timeout',
        ];

        return new CashbackResult(
            success: false,
            failureReason: $failureReasons[array_rand($failureReasons)],
        );
    }
}
