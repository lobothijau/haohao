<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Http\Requests\SubscribeRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function __construct(private MidtransService $midtransService) {}

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

        $existingPending = $user->subscriptions()
            ->where('status', SubscriptionStatus::Pending)
            ->where('plan_id', $plan->id)
            ->whereNotNull('snap_token')
            ->first();

        if ($existingPending) {
            return redirect()->route('membership.checkout', $existingPending);
        }

        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'amount' => $plan->price,
        ]);

        try {
            $result = $this->midtransService->createSnapTransaction($user, $plan, $subscription);

            $subscription->update([
                'snap_token' => $result['snap_token'],
                'midtrans_order_id' => $result['order_id'],
            ]);
        } catch (\Exception $e) {
            $subscription->delete();

            return redirect()->route('membership.index')
                ->with('error', 'Gagal memproses pembayaran. Silakan coba lagi.');
        }

        return redirect()->route('membership.checkout', $subscription);
    }

    public function checkout(Request $request, Subscription $subscription): Response
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        abort_unless($subscription->status === SubscriptionStatus::Pending, 404);

        $subscription->load('plan');

        return Inertia::render('Membership/Checkout', [
            'subscription' => $subscription,
            'snapToken' => $subscription->snap_token,
            'clientKey' => $this->midtransService->clientKey(),
            'isProduction' => $this->midtransService->isProduction(),
        ]);
    }
}
