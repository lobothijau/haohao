<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Http\Requests\SubscribeRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MockPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Membership/Index', [
            'plans' => $plans,
            'activeSubscription' => $user?->activeSubscription()?->load('plan'),
        ]);
    }

    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        $plan = Plan::where('slug', $request->validated('plan'))->where('is_active', true)->firstOrFail();
        $user = $request->user();

        if ($plan->slug === 'founder' && ! Plan::isFounderAvailable()) {
            $plan->update(['is_active' => false]);

            return redirect()->route('membership.index')
                ->with('error', 'Maaf, kuota Founder Edition sudah habis.');
        }

        $paymentService = new MockPaymentService;
        $order = $paymentService->createOrder($user, $plan);

        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'midtrans_order_id' => $order['order_id'],
            'amount' => $order['amount'],
        ]);

        return redirect()->route('membership.checkout', $subscription);
    }

    public function checkout(Request $request, Subscription $subscription): Response
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        abort_unless($subscription->status === SubscriptionStatus::Pending, 404);

        $subscription->load('plan');

        return Inertia::render('Membership/Checkout', [
            'subscription' => $subscription,
        ]);
    }

    public function processPayment(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        abort_unless($subscription->status === SubscriptionStatus::Pending, 404);

        $subscription->load('plan');

        $paymentService = new MockPaymentService;
        $result = $paymentService->processPayment($subscription->midtrans_order_id);

        $startsAt = now();
        $expiresAt = $startsAt->copy()->addMonths($subscription->plan->duration_months);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'midtrans_transaction_id' => $result['transaction_id'],
            'payment_method' => 'mock',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ]);

        $request->user()->activatePremium($expiresAt);

        if ($subscription->plan->slug === 'founder') {
            Cache::forget('founder_claimed_count');

            if (! Plan::isFounderAvailable()) {
                Plan::where('slug', 'founder')->update(['is_active' => false]);
            }
        }

        return redirect()->route('membership.index')->with('success', 'Pembayaran berhasil! Selamat menikmati akses premium.');
    }
}
