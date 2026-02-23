<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark active subscriptions past their expiry date as expired';

    public function handle(): int
    {
        $expiredIds = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            $this->info('No subscriptions to expire.');

            return self::SUCCESS;
        }

        Subscription::query()
            ->whereIn('id', $expiredIds)
            ->update(['status' => SubscriptionStatus::Expired]);

        $affectedUserIds = Subscription::query()
            ->whereIn('id', $expiredIds)
            ->distinct('user_id')
            ->pluck('user_id');

        foreach ($affectedUserIds as $userId) {
            $hasActiveSubscription = Subscription::query()
                ->where('user_id', $userId)
                ->where('status', SubscriptionStatus::Active)
                ->exists();

            if (! $hasActiveSubscription) {
                User::query()
                    ->where('id', $userId)
                    ->update(['is_premium' => false]);
            }
        }

        $this->info("Expired {$expiredIds->count()} subscription(s).");

        return self::SUCCESS;
    }
}
