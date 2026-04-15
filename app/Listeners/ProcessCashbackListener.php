<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use App\Models\CashbackTransaction;
use App\Services\LoyaltyService;
use App\Services\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCashbackListener implements ShouldQueue
{
    public function __construct(
        private LoyaltyService $loyaltyService,
        private PaymentGatewayInterface $gateway,
    ) {
    }

    public function handle(PurchaseCompleted $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (! $user) {
            return;
        }

        $percentage = $this->loyaltyService->calculateCashbackPercentage($user);

        if ($percentage <= 0) {
            return;
        }

        $cashbackAmount = round($order->total_amount * ($percentage / 100), 2);

        $transaction = CashbackTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => $cashbackAmount,
            'payment_provider' => config('services.payment_gateway.provider', 'paystack'),
            'status' => 'pending',
        ]);

        $result = $this->gateway->disburseCashback(
            $user,
            $cashbackAmount,
            'ORD-' . $order->id,
        );

        $transaction->update([
            'status' => $result->success ? 'success' : 'failed',
            'provider_reference' => $result->providerReference,
            'failure_reason' => $result->failureReason,
        ]);
    }
}
