<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MidtransWebhookController extends Controller
{
    public function __construct(private MidtransService $midtransService) {}

    public function __invoke(): JsonResponse
    {
        $notification = $this->midtransService->parseNotification();

        $orderId = $notification->order_id;
        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status ?? null;
        $paymentType = $notification->payment_type ?? null;
        $transactionId = $notification->transaction_id ?? null;

        $subscription = Subscription::where('midtrans_order_id', $orderId)->first();

        if (! $subscription) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($subscription->status !== SubscriptionStatus::Pending) {
            return response()->json(['message' => 'Already processed']);
        }

        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
            $this->activateSubscription($subscription, $transactionId, $paymentType);
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $subscription->update([
                'status' => SubscriptionStatus::Failed,
                'midtrans_transaction_id' => $transactionId,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    private function activateSubscription(Subscription $subscription, ?string $transactionId, ?string $paymentType): void
    {
        $subscription->load('plan', 'user');

        $startsAt = now();
        $expiresAt = $startsAt->copy()->addMonths($subscription->plan->duration_months);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'midtrans_transaction_id' => $transactionId,
            'payment_method' => $paymentType,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ]);

        $subscription->user->activatePremium($expiresAt);

        if ($subscription->plan->slug === 'founder') {
            Cache::forget('founder_claimed_count');

            if (! Plan::isFounderAvailable()) {
                Plan::where('slug', 'founder')->update(['is_active' => false]);
            }
        }
    }
}
