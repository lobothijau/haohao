<?php

namespace App\Models;

use App\Enums\CardState;
use App\Enums\SrsRating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SrsReviewLog extends Model
{
    /** @use HasFactory<\Database\Factories\SrsReviewLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'srs_card_id',
        'rating',
        'previous_state',
        'new_state',
        'previous_interval',
        'new_interval',
        'previous_ease',
        'new_ease',
        'time_taken_ms',
        'reviewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => SrsRating::class,
            'previous_state' => CardState::class,
            'new_state' => CardState::class,
            'previous_ease' => 'decimal:2',
            'new_ease' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SrsCard, $this>
     */
    public function srsCard(): BelongsTo
    {
        return $this->belongsTo(SrsCard::class);
    }
}
