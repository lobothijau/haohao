<?php

namespace App\Models;

use App\Enums\CardState;
use App\Enums\CardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SrsCard extends Model
{
    /** @use HasFactory<\Database\Factories\SrsCardFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'dictionary_entry_id',
        'user_vocabulary_id',
        'card_state',
        'ease_factor',
        'interval_days',
        'repetitions',
        'lapses',
        'learning_step',
        'due_at',
        'last_reviewed_at',
        'graduated_at',
        'card_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'card_state' => CardState::class,
            'card_type' => CardType::class,
            'ease_factor' => 'decimal:2',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'graduated_at' => 'datetime',
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
     * @return BelongsTo<DictionaryEntry, $this>
     */
    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DictionaryEntry::class);
    }

    /**
     * @return BelongsTo<UserVocabulary, $this>
     */
    public function userVocabulary(): BelongsTo
    {
        return $this->belongsTo(UserVocabulary::class);
    }

    /**
     * @return HasMany<SrsReviewLog, $this>
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(SrsReviewLog::class);
    }
}
