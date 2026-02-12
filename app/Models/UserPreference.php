<?php

namespace App\Models;

use App\Enums\CardOrder;
use App\Enums\CharacterSet;
use App\Enums\FontSize;
use App\Enums\ReadingMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserPreferenceFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'show_pinyin',
        'show_translation',
        'font_size',
        'reading_mode',
        'character_set',
        'new_cards_per_day',
        'max_reviews_per_day',
        'card_order',
        'daily_reminder',
        'reminder_time',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_pinyin' => 'boolean',
            'show_translation' => 'boolean',
            'font_size' => FontSize::class,
            'reading_mode' => ReadingMode::class,
            'character_set' => CharacterSet::class,
            'card_order' => CardOrder::class,
            'daily_reminder' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
