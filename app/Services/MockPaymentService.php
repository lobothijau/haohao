<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

class MockPaymentService
{
    /**
     * @return array{order_id: string, amount: int}
     */
    public function createOrder(User $user, Plan $plan): array
    {
        return [
            'order_id' => 'ORDER-'.strtoupper(Str::random(12)),
            'amount' => $plan->price,
        ];
    }

    /**
     * @return array{transaction_id: string, status: string}
     */
    public function processPayment(string $orderId): array
    {
        return [
            'transaction_id' => 'TXN-'.strtoupper(Str::random(12)),
            'status' => 'success',
        ];
    }
}
