<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'label',
        'price',
        'duration_months',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_months' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function founderClaimedCount(): int
    {
        return Cache::remember('founder_claimed_count', 300, function () {
            return Subscription::query()
                ->whereHas('plan', fn ($q) => $q->where('slug', 'founder'))
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Expired])
                ->distinct('user_id')
                ->count('user_id');
        });
    }

    public static function founderLimit(): int
    {
        return config('app.founder_limit');
    }

    public static function isFounderAvailable(): bool
    {
        return static::founderClaimedCount() < static::founderLimit();
    }
}
