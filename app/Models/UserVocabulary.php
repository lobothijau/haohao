<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserVocabulary extends Model
{
    /** @use HasFactory<\Database\Factories\UserVocabularyFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'dictionary_entry_id',
        'source_story_id',
        'source_sentence_id',
        'user_note',
    ];

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
     * @return BelongsTo<Story, $this>
     */
    public function sourceStory(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'source_story_id');
    }

    /**
     * @return HasOne<SrsCard, $this>
     */
    public function srsCard(): HasOne
    {
        return $this->hasOne(SrsCard::class);
    }
}
