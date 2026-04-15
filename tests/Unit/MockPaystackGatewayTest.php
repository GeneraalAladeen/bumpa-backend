<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\MockPaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockPaystackGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_cashback_result(): void
    {
        $gateway = new MockPaystackGateway();
        $user = User::factory()->create();

        $result = $gateway->disburseCashback($user, 100.00, 'REF-001');

        $this->assertIsBool($result->success);

        if ($result->success) {
            $this->assertNotNull($result->providerReference);
            $this->assertStringStartsWith('PSK_', $result->providerReference);
            $this->assertNull($result->failureReason);
        } else {
            $this->assertNotNull($result->failureReason);
        }
    }

    public function test_always_succeeds_with_100_percent_rate(): void
    {
        config(['services.payment_gateway.success_rate' => 100]);
        $gateway = new MockPaystackGateway();
        $user = User::factory()->create();

        $result = $gateway->disburseCashback($user, 50.00, 'REF-002');

        $this->assertTrue($result->success);
        $this->assertNotNull($result->providerReference);
    }

    public function test_always_fails_with_0_percent_rate(): void
    {
        config(['services.payment_gateway.success_rate' => 0]);
        $gateway = new MockPaystackGateway();
        $user = User::factory()->create();

        $result = $gateway->disburseCashback($user, 50.00, 'REF-003');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->failureReason);
    }
}
