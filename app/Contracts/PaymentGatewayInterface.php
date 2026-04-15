<?php

namespace App\Contracts;

use App\Models\User;
use App\Services\CashbackResult;

interface PaymentGatewayInterface
{
    public function disburseCashback(User $user, float $amount, string $reference): CashbackResult;
}
