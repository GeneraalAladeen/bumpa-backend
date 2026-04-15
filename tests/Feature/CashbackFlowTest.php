<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\Events\PurchaseCompleted;
use App\Listeners\ProcessCashbackListener;
use App\Models\Achievement;
use App\Models\Order;
use App\Models\User;
use App\Services\CashbackResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CashbackFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashback_created_on_successful_payment(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('disburseCashback')
            ->once()
            ->andReturn(new CashbackResult(success: true, providerReference: 'PSK_TEST123'));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'required_purchase_count' => 1,
            'cashback_percentage' => 5,
        ]);
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => 'completed',
        ]);

        $listener = app(ProcessCashbackListener::class);
        $listener->handle(new PurchaseCompleted($order));

        $this->assertDatabaseHas('cashback_transactions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => 50.00, // 5% of 1000
            'status' => 'success',
            'provider_reference' => 'PSK_TEST123',
        ]);
    }

    public function test_cashback_records_failure(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('disburseCashback')
            ->once()
            ->andReturn(new CashbackResult(success: false, failureReason: 'Insufficient funds'));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'required_purchase_count' => 1,
            'cashback_percentage' => 3,
        ]);
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 2000,
            'status' => 'completed',
        ]);

        $listener = app(ProcessCashbackListener::class);
        $listener->handle(new PurchaseCompleted($order));

        $this->assertDatabaseHas('cashback_transactions', [
            'user_id' => $user->id,
            'status' => 'failed',
            'failure_reason' => 'Insufficient funds',
        ]);
    }

    public function test_no_cashback_when_no_achievements(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => 'completed',
        ]);

        $listener = app(ProcessCashbackListener::class);
        $listener->handle(new PurchaseCompleted($order));

        $this->assertDatabaseCount('cashback_transactions', 0);
    }
}
